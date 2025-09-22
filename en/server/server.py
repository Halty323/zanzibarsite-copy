#!/usr/bin/env python3
"""
Python 3.9 HTTP Server implementing the functionality of the Node.js server
without using any third-party libraries.
"""

import http.server
import socketserver
import json
import os
import sys
import shutil
import cgi
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import urllib.parse
import base64

# Configuration
PORT = int(os.environ.get('PORT', 3000))
UPLOAD_DIR = os.path.join(os.path.dirname(__file__), '../')

class CustomHTTPRequestHandler(http.server.BaseHTTPRequestHandler):
    def end_headers(self):
        # Add CORS headers
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, indexing')
        super().end_headers()

    def do_OPTIONS(self):
        self.send_response(200)
        self.end_headers()

    def authenticate(self):
        """Authentication middleware"""
        if 'indexing' not in self.headers:
            self.send_response(401)
            self.end_headers()
            self.wfile.write(b'Unauthorized')
            return False

        if self.headers['indexing'] != 'authorizationEnabled':
            self.send_response(401)
            self.end_headers()
            self.wfile.write(b'Unauthorized')
            return False

        return True

    def get_json_body(self):
        """Parse JSON body from request"""
        if 'Content-Length' not in self.headers:
            return None

        content_length = int(self.headers['Content-Length'])
        if content_length == 0:
            return None

        post_data = self.rfile.read(content_length)
        try:
            return json.loads(post_data.decode('utf-8'))
        except:
            return None

    def send_json_response(self, data, status=200):
        """Send JSON response"""
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode('utf-8'))

    def send_text_response(self, text, status=200):
        """Send plain text response"""
        self.send_response(status)
        self.send_header('Content-Type', 'text/plain')
        self.end_headers()
        self.wfile.write(text.encode('utf-8'))

    def do_POST(self):
        if self.path == '/send-email':
            self.handle_send_email()
        elif self.path == '/upload':
            self.handle_upload()
        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b'Not Found')

    def do_DELETE(self):
        if self.path.startswith('/delete/'):
            if not self.authenticate():
                return
            self.handle_delete_file()
        elif self.path.startswith('/delete-folder/'):
            if not self.authenticate():
                return
            self.handle_delete_folder()
        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b'Not Found')

    def do_GET(self):
        if self.path == '/files':
            if not self.authenticate():
                return
            self.handle_file_tree()
        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b'Not Found')

    def handle_send_email(self):
        """Handle email sending"""
        data = self.get_json_body()
        if not data:
            self.send_response(400)
            self.end_headers()
            self.wfile.write(b'Invalid JSON data')
            return

        # Extract form data
        first_name = data.get('first-name', '')
        last_name = data.get('last-name', '')
        email = data.get('email', '')
        telephone = data.get('telephone', '')
        subject = data.get('subject', '')
        message = data.get('comment', '')

        if not all([first_name, last_name, email, subject, message]):
            self.send_response(400)
            self.end_headers()
            self.wfile.write(b'Missing required fields')
            return

        # Email configuration
        smtp_server = 'smtp.gmail.com'
        smtp_port = 587
        sender_email = 'haltyhalty12@gmail.com'
        sender_password = ''  # Add your Gmail app password here
        receiver_email = 'ddeenn_1980@mail.ru'

        try:
            # Create message
            msg = MIMEMultipart()
            msg['From'] = email
            msg['To'] = receiver_email
            msg['Subject'] = f'Contact Form Submission: {subject}'

            body = f'''Name: {first_name} {last_name}
Email: {email}
Telephone: {telephone}
Message: {message}'''

            msg.attach(MIMEText(body, 'plain'))

            # Send email
            server = smtplib.SMTP(smtp_server, smtp_port)
            server.starttls()
            server.login(sender_email, sender_password)
            server.send_message(msg)
            server.quit()

            print('Email sent successfully')
            self.send_text_response('Email sent successfully', 200)

        except Exception as e:
            print(f'Email sending failed: {str(e)}')
            self.send_response(500)
            self.end_headers()
            self.wfile.write(f'Failed to send email: {str(e)}'.encode('utf-8'))

    def handle_upload(self):
        """Handle file upload"""
        if not self.authenticate():
            return

        if 'file' not in self.headers.get('Content-Type', '').lower():
            self.send_response(400)
            self.end_headers()
            self.wfile.write(b'No file uploaded')
            return

        try:
            # Parse multipart form data
            form = cgi.FieldStorage(
                fp=self.rfile,
                headers=self.headers,
                environ={'REQUEST_METHOD': 'POST'}
            )

            if 'file' not in form:
                self.send_response(400)
                self.end_headers()
                self.wfile.write(b'No file field found')
                return

            file_item = form['file']
            if file_item.filename:
                # Save file to upload directory
                file_path = os.path.join(UPLOAD_DIR, file_item.filename)

                with open(file_path, 'wb') as f:
                    f.write(file_item.file.read())

                self.send_text_response('File uploaded successfully', 200)
            else:
                self.send_response(400)
                self.end_headers()
                self.wfile.write(b'No file selected')

        except Exception as e:
            print(f'Upload error: {str(e)}')
            self.send_response(500)
            self.end_headers()
            self.wfile.write(f'Upload failed: {str(e)}'.encode('utf-8'))

    def handle_delete_file(self):
        """Handle file deletion"""
        filename = self.path.split('/delete/')[1]
        if not filename:
            self.send_response(400)
            self.end_headers()
            self.wfile.write(b'Filename required')
            return

        file_path = os.path.join(UPLOAD_DIR, filename)

        try:
            if os.path.exists(file_path):
                os.unlink(file_path)
                self.send_text_response('File deleted successfully', 200)
            else:
                self.send_response(404)
                self.end_headers()
                self.wfile.write(b'File not found')

        except Exception as e:
            print(f'Delete error: {str(e)}')
            self.send_response(500)
            self.end_headers()
            self.wfile.write(f'Error deleting file: {str(e)}'.encode('utf-8'))

    def handle_delete_folder(self):
        """Handle folder deletion"""
        foldername = self.path.split('/delete-folder/')[1]
        if not foldername:
            self.send_response(400)
            self.end_headers()
            self.wfile.write(b'Folder name required')
            return

        folder_path = os.path.join(UPLOAD_DIR, foldername)

        try:
            if os.path.exists(folder_path):
                shutil.rmtree(folder_path)
                self.send_text_response('Folder deleted successfully', 200)
            else:
                self.send_response(404)
                self.end_headers()
                self.wfile.write(b'Folder not found')

        except Exception as e:
            print(f'Delete folder error: {str(e)}')
            self.send_response(500)
            self.end_headers()
            self.wfile.write(f'Error deleting folder: {str(e)}'.encode('utf-8'))

    def build_file_tree(self, dir_path, prefix=''):
        """Build file tree representation"""
        result = ''
        try:
            files = os.listdir(dir_path)
            dirs = [f for f in files if os.path.isdir(os.path.join(dir_path, f))]

            for i, dir_name in enumerate(dirs):
                is_last = i == len(dirs) - 1
                connector = '└── ' if is_last else '├── '
                next_prefix = '    ' if is_last else '│   '

                result += prefix + connector + dir_name + '\n'

                sub_dir_path = os.path.join(dir_path, dir_name)
                result += self.build_file_tree(sub_dir_path, prefix + next_prefix)

        except Exception as e:
            print(f'Error building file tree: {str(e)}')

        return result

    def handle_file_tree(self):
        """Handle file tree listing"""
        try:
            file_tree = self.build_file_tree(UPLOAD_DIR)
            self.send_text_response(file_tree, 200)
        except Exception as e:
            print(f'Error reading file tree: {str(e)}')
            self.send_response(500)
            self.end_headers()
            self.wfile.write(b'Error reading file tree')

def main():
    try:
        with socketserver.TCPServer(("", PORT), CustomHTTPRequestHandler) as httpd:
            print(f"Server running on port {PORT}")
            httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nServer stopped.")
    except Exception as e:
        print(f"Error starting server: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    main()