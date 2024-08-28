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
var groups = [];

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

redis.subscribe('group-channel', (err, count) => {
    if (err) {
        console.error('Subscription error:', err);
    } else {
        console.log('Subscribed to group-channel, subscription count:', count);
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
    if (channel == 'group-channel') {
        let data = message.data.data;

        if (data.type == 2) {
            let socket_id = getSocketIdOfUserInGroup(data.sender_id, data.group_id);
            console.log('socket_id: ' + socket_id);
            let socket = io.sockets.sockets.get(socket_id);
            console.log('socket: ' + socket);
            socket.broadcast.to('group' + data.group_id).emit('groupMessage', data);
        }
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

    socket.on('joinGroup', (data) => {
        console.log(data);
        data['socket_id'] = socket.id;
        if (groups[data.group_id]) {
            var userExist = checkIfUserExistInGroup(data.group_id, data.user_id);

            if (!userExist) {
                groups[data.group_id].push(data);
                socket.join(data.room);
            } else {
                var index = groups[data.group_id].map(function (o) {
                    return o.user_id;
                }).indexOf(data.user_id);

                groups[data.group_id].splice(index, 1);
                groups[data.group_id].push(data);
                socket.join(data.room);
            }
        } else {
            console.log("new group");
            groups[data.group_id] = [data];
            socket.join(data.room);

        }

        console.log('socket-id: ' + socket.id + ' - user-id: ' + data.user_id);
        console.log(groups);
    });
});


function checkIfUserExistInGroup(group_id, user_id) {
    var group = groups[group_id];
    var exist = false;
    if (groups.length > 0) {
        for (var i = 0; i < group.length; i++) {
            if (group[i]['user_id'] == user_id) {
                exist = true;
                break;
            }
        }
    }

    return exist;
}

function getSocketIdOfUserInGroup(user_id, group_id) {
    var group = groups[group_id];
    if (groups.length > 0) {
        for (var i = 0; i < group.length; i++) {
            if (group[i]['user_id'] == user_id) {
                return group[i]['socket_id'];
            }
        }
    }
}
