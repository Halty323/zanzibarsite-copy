# Hidden Garden Zanzibar - PHP Server

This is a PHP 8.2.28 rewrite of the original Node.js/Express server for the Hidden Garden Zanzibar project.

## Features

- **Email sending** - Contact form submissions via PHPMailer
- **File upload** - Secure file upload with authentication
- **File management** - Delete files and folders with authentication
- **File tree browsing** - View directory structure with authentication
- **CORS support** - Cross-origin resource sharing enabled
- **Authentication** - Header-based authentication system

## Requirements

- PHP 8.2.28 or higher
- Web server with PHP support (Apache, Nginx, or built-in PHP server)
- PHP mail() function enabled (usually enabled by default)

## Installation

1. Navigate to the server directory:
   ```bash
   cd server
   ```

2. Configure email settings in `server.php`:
   - The server uses PHP's built-in mail() function
   - No external dependencies required
   - Ensure your server is configured to send emails

## Configuration

### Email Settings
Edit the email configuration in `server.php`:

```php
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password'; // Use App Password, not regular password
```

### Authentication
The server uses header-based authentication. Include the following header in authenticated requests:
```
indexing: authorizationEnabled
```

## Running the Server

### Option 1: Built-in PHP Server (Development)
```bash
php -S localhost:3000 server.php
```

### Option 2: Apache/Nginx
Configure your web server to point to the server directory and route requests to `server.php`.

For Apache, you may need to add to your `.htaccess`:
```apache
RewriteEngine On
RewriteRule ^(.*)$ server.php [QSA,L]
```

### Option 3: Shared Hosting Deployment

For shared hosting environments (like Hostinger, GoDaddy, etc.):

1. **Upload Files**: Upload all files from the `server` directory to your `public_html` or `www` directory via FTP/SFTP

2. **File Permissions**: Ensure `server.php` has execute permissions (755 or 644)

3. **URL Structure**: Access your server at `https://yourdomain.com/server.php`

4. **Direct Access**: For cleaner URLs, you can:
   - Rename `server.php` to `index.php` in the server directory
   - Upload the server directory contents to your root directory
   - Or configure your hosting to route requests to `server.php`

5. **Email Configuration**: Update the email settings in `server.php` to match your hosting:
   ```php
   // For most shared hosting, use these settings:
   ini_set('SMTP', 'mail.yourdomain.com'); // Your hosting SMTP
   ini_set('smtp_port', 587); // Usually 587 or 25
   ini_set('sendmail_from', 'your-email@yourdomain.com');
   ```

6. **Test the Server**:
   - Visit: `https://yourdomain.com/server.php/health`
   - Test email: Send POST to `https://yourdomain.com/server.php/send-email`

## API Endpoints

### POST /send-email
Send contact form emails.

**Request Body:**
```json
{
    "first-name": "John",
    "last-name": "Doe",
    "email": "john@example.com",
    "telephone": "+1234567890",
    "subject": "Contact Request",
    "comment": "Message content"
}
```

### POST /upload
Upload files (requires authentication).

**Headers:**
```
indexing: authorizationEnabled
Content-Type: multipart/form-data
```

### DELETE /delete/{filename}
Delete a file (requires authentication).

### DELETE /delete-folder/{foldername}
Delete a folder recursively (requires authentication).

### GET /files
Get file tree structure (requires authentication).

### GET /health
Check server status (no authentication required).

**Response:**
```json
{
    "status": "ok",
    "message": "PHP Server is running",
    "php_version": "8.2.28",
    "server_software": "PHP Development Server"
}
```

## Security Notes

- All file operations require authentication
- File uploads are saved to the parent directory
- Email credentials should be properly secured
- Consider implementing additional security measures for production

## Shared Hosting Specific Notes

### Email Configuration for Shared Hosting
Most shared hosting providers require specific SMTP settings. Update your `server.php`:

```php
// Add this at the top of your server.php file, after <?php
ini_set('SMTP', 'mail.yourdomain.com'); // Replace with your hosting SMTP
ini_set('smtp_port', 587);
ini_set('sendmail_from', 'your-email@yourdomain.com');

// Or use direct SMTP settings in the mail() function
$headers = "From: your-email@yourdomain.com\r\n";
$headers .= "Reply-To: your-email@yourdomain.com\r\n";
```

### Common Shared Hosting Issues

1. **File Permissions**: Ensure files have 644 and directories have 755 permissions
2. **Memory Limits**: Some hosts have low PHP memory limits - contact support if needed
3. **Email Limits**: Shared hosts often limit emails per hour - check your plan
4. **URL Rewriting**: If clean URLs don't work, access endpoints directly:
   - `https://yourdomain.com/server.php/send-email`
   - `https://yourdomain.com/server.php/files`

### Testing on Shared Hosting

1. **Upload Files**: Use FTP/SFTP to upload the `server` directory
2. **Set Permissions**: Right-click files in FTP client and set 644/755 permissions
3. **Test Health**: Visit `https://yourdomain.com/server/server.php/health`
4. **Test Email**: Send a test contact form to verify email works
5. **Check Logs**: Most hosts provide error logs in cPanel/File Manager

### Quick Deployment Checklist

- [ ] Upload all files from `server/` directory to your hosting
- [ ] Set file permissions (644 for files, 755 for directories)
- [ ] Update email settings in `server.php` for your hosting
- [ ] Test `/health` endpoint
- [ ] Test `/send-email` with a contact form
- [ ] Verify file upload and deletion work
- [ ] Check error logs if something doesn't work

### Example FTP Upload Structure
```
public_html/
├── server/
│   ├── server.php
│   ├── .htaccess
│   ├── composer.json
│   └── README.md
```

Access your API at:
- Health check: `https://yourdomain.com/server/server.php/health`
- Contact form: `https://yourdomain.com/server/server.php/send-email`
- File operations: `https://yourdomain.com/server/server.php/files`

## Migration from Node.js

This PHP version maintains the same API endpoints and functionality as the original Node.js server:

- Same authentication mechanism
- Identical request/response formats
- Equivalent file handling capabilities
- Same email functionality using PHP's built-in mail() function instead of Nodemailer

## Troubleshooting

1. **Email not sending**: Ensure PHP's mail() function is enabled and your server is configured to send emails
2. **File permissions**: Ensure PHP has write permissions for file operations
3. **CORS issues**: CORS headers are already configured, check browser console for errors
4. **Authentication failures**: Verify the `indexing` header is correctly set
5. **Server requirements**: Make sure your server meets the minimum PHP 8.2.28 requirement
6. **Server errors**: If you see "Undefined array key" errors, ensure you're running the server from the correct directory
7. **Health check**: Visit `/health` endpoint to verify server is running properly
8. **Path issues**: Make sure the server.php file is in the correct location relative to your files