const express = require('express');
const nodemailer = require('nodemailer');
const bodyParser = require('body-parser');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(bodyParser.json());

app.post('/send-email', async (req, res) => {
    const { 'first-name': firstName, 'last-name': lastName, email, telephone, subject, comment: message } = req.body;

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

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});