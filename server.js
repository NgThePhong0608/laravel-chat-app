import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';
import { Redis } from 'ioredis';
import cors from 'cors';

const redis = new Redis();
const app = express();
const http = createServer(app);
const port = 3000;


const io = new Server(http, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

var users = [];

http.listen(port, () => {
    console.log(`Server is running on port ${port}`);
}).on('error', (err) => {
    console.error('Server error:', err);
});


redis.subscribe('private-channel', (err, count) => {
    if (err) {
        console.error('Subscription error:', err);
    } else {
        console.log('Subscribed to private-channel, subscription count:', count);
    }
});


redis.on('message', (channel, message) => {
    message = JSON.parse(message);
    console.log(message);
    if (channel == 'private-channel') {
        let data = message.data.data;
        let receiver_id = data.receiver_id;
        let event = message.event;

        io.to(`${users[receiver_id]}`).emit(channel + ':' + event, data);
    }
});


io.on('connection', (socket) => {
    socket.on('user_connected', (userId) => {
        users[userId] = socket.id;
        io.emit('updateUserStatus', users);
        console.log('User connected:', userId);
    });

    socket.on('disconnect', () => {
        var i = users.indexOf(socket.id);
        users.splice(i, 1, 0);
        io.emit('updateUserStatus', users);
        console.log(users);
    });
});