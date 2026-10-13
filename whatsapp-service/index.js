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
let latestQr = null;

client.on('qr', (qr) => {
    console.log('SCAN THIS QR CODE WITH YOUR WHATSAPP:');
    latestQr = qr;
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    console.log('WhatsApp Client is ready!');
    isClientReady = true;
    latestQr = null;
});

client.on('auth_failure', msg => {
    console.error('AUTHENTICATION FAILURE', msg);
    isClientReady = false;
    latestQr = null;
});

client.on('disconnected', (reason) => {
    console.log('Client was logged out', reason);
    isClientReady = false;
    latestQr = null;
});

client.on('message_create', async msg => {
    try {
        const text = (msg.body || '').trim().toLowerCase();
        
        // Prevent loop if bot replies to itself
        if (text.startsWith('pong!') || text.startsWith('halo!')) {
            return;
        }

        if (text === '!ping') {
            await msg.reply('Pong! Bot WhatsApp CBM aktif dan berjalan lancar. ✅');
        } else if (text === '!help' || text === '!menu') {
            await msg.reply("Halo! Saya adalah CBM Assistant Bot di WhatsApp 🤖\n\nPerintah tersedia:\n!ping - Cek status bot\n!help - Bantuan perintah");
        }
    } catch (err) {
        console.error('Error handling message:', err);
    }
});

client.initialize();

// Status check endpoint
app.get('/status', (req, res) => {
    return res.status(200).json({
        ready: isClientReady,
        qr: latestQr,
        message: isClientReady 
            ? 'WhatsApp client is connected.' 
            : (latestQr ? 'Silakan scan QR code dengan WhatsApp di ponsel Anda.' : 'WhatsApp client is connecting or disconnected.')
    });
});

// Restart endpoint to trigger re-pairing
app.post('/restart', async (req, res) => {
    try {
        isClientReady = false;
        latestQr = null;
        await client.destroy();
        client.initialize();
        return res.status(200).json({ status: 'success', message: 'Client re-initializing...' });
    } catch (err) {
        console.error('Failed to restart client:', err);
        return res.status(500).json({ status: 'error', message: err.message });
    }
});

// API Endpoint to get all WhatsApp groups
app.get('/groups', async (req, res) => {
    if (!isClientReady) {
        return res.status(503).json({ status: 'error', message: 'WhatsApp client is not ready yet.', groups: [] });
    }

    try {
        const chats = await client.getChats();
        const groups = chats
            .filter(chat => chat.isGroup)
            .map(chat => ({
                id: chat.id._serialized,
                name: chat.name,
                unreadCount: chat.unreadCount || 0
            }));
        return res.status(200).json({ status: 'success', groups });
    } catch (error) {
        console.error('Failed to get groups:', error);
        return res.status(500).json({ status: 'error', message: 'Failed to fetch groups.', error: error.message, groups: [] });
    }
});

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
        let targetId = number.toString().trim();

        // Check if it's already a full JID (contains @g.us for groups or @c.us for individuals)
        if (targetId.includes('@')) {
            // Use targetId as is
        } else {
            // Format phone number
            let formattedNumber = targetId.replace(/[^0-9]/g, '');
            if (formattedNumber.startsWith('0')) {
                formattedNumber = '62' + formattedNumber.substring(1);
            }
            targetId = `${formattedNumber}@c.us`;
        }

        await client.sendMessage(targetId, message);
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
