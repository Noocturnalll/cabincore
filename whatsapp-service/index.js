const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const express = require('express');

const app = express();
app.use(express.json());

const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: {
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    }
});

let isClientReady = false;

client.on('qr', (qr) => {
    console.log('SCAN THIS QR CODE WITH YOUR WHATSAPP:');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    console.log('WhatsApp Client is ready!');
    isClientReady = true;
});

client.on('auth_failure', msg => {
    console.error('AUTHENTICATION FAILURE', msg);
});

client.on('disconnected', (reason) => {
    console.log('Client was logged out', reason);
    isClientReady = false;
});

client.initialize();

// API Endpoint for Laravel to send messages
app.post('/send-message', async (req, res) => {
    if (!isClientReady) {
        return res.status(503).json({ status: 'error', message: 'WhatsApp client is not ready yet.' });
    }

    const { number, message } = req.body;

    if (!number || !message) {
        return res.status(400).json({ status: 'error', message: 'Number and message are required.' });
    }

    try {
        // Format number: remove +, replace leading 0 with 62
        let formattedNumber = number.toString().replace(/[^0-9]/g, '');
        if (formattedNumber.startsWith('0')) {
            formattedNumber = '62' + formattedNumber.substring(1);
        }
        
        // Add @c.us suffix if it's not a group
        if (!formattedNumber.includes('@')) {
            formattedNumber = `${formattedNumber}@c.us`;
        }

        await client.sendMessage(formattedNumber, message);
        return res.status(200).json({ status: 'success', message: 'Message sent successfully.' });
    } catch (error) {
        console.error('Failed to send message:', error);
        return res.status(500).json({ status: 'error', message: 'Failed to send message.', error: error.message });
    }
});

const PORT = 3001;
app.listen(PORT, () => {
    console.log(`WhatsApp API Server running on port ${PORT}`);
});
