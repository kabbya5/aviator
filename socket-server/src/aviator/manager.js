const axios = require('axios');
let Enigine = require('./enigine');

class Manager {

    constructor(io) {

        this.io = io;

        this.users = {};

        this.started = false;

        // SINGLE GAME ENGINE
        this.room = new Enigine(io, 'aviator');
    }

    joinRoom(socket, roomKey, user_id) {

        const rooms = Array.from(socket.rooms);

        rooms.forEach(r => {

            if (r !== socket.id && r !== roomKey) {

                socket.leave(r);

                if (this.users[r]) {

                    this.users[r] = this.users[r].filter(
                        u => u.socketId !== socket.id
                    );

                    this.io.to(r).emit('user:update', {
                        users: this.users[r] || []
                    });
                }
            }
        });

        socket.user_id = user_id;

        socket.roomKey = roomKey;

        socket.join(roomKey);

        // FIXED
        const timer = this.room;

        if (timer) {

            socket.emit('round:new', {
                roundId: timer.roundId,
                bettingTime: timer.betTime
            });

            socket.emit('betting:timer', {
                time: timer.timeLeft
            });

            socket.emit('multiplier:update', {
                multiplier: timer.multiplier
            });
        }

        if (!this.users[roomKey]) {
            this.users[roomKey] = [];
        }

        const exists = this.users[roomKey].find(
            u => u.user_id === user_id
        );

        if (!exists) {

            this.users[roomKey].push({
                socketId: socket.id,
                user_id
            });
        }

        this.io.to(roomKey).emit('user:update', {
            users: this.users[roomKey] || []
        });

        // START ENGINE ONLY ONCE
        if (!this.started) {

            this.started = true;

            this.room.start();
        }
    }

    handleDisconnect(socket, reason) {

        const { roomKey,user_id } = socket;

        // if(user_id){
        //     axios.get('http://127.0.0.1:8000/aviator/temp/bet/delete', {
        //         params: {
        //             user_id: user_id
        //         }
        //     });
        // }

        if (!roomKey || !this.users[roomKey]) return;

        this.users[roomKey] = this.users[roomKey].filter(
            u => u.socketId !== socket.id
        );

        this.io.to(roomKey).emit('user:update', {
            users: this.users[roomKey] || []
        });
    }

    forceCrash(point = 1){
        this.room.forceCrash(point);
    }

    updateCrashPoint(point){
        this.room.updateCrashPoint(point);
    }
}

module.exports = Manager;
