const TimerEngine = require('./TimerEngine');
const ROOMS = require('./room');

class RoomManager {
    constructor(io) {
        this.io = io;
        this.rooms = {};
        this.users = {};

        for (let roomKey in ROOMS) {
            const duration = ROOMS[roomKey].duration;
            this.rooms[roomKey] = new TimerEngine(io, roomKey, duration);
            this.rooms[roomKey].start();
        }
    }

    joinRoom(socket, roomKey, user_id) {
        const rooms = Array.from(socket.rooms);
        rooms.forEach(r => {
            if (r !== socket.id && r !== roomKey) {
                socket.leave(r);

                if (this.users[r]) {
                    this.users[r] = this.users[r].filter(u => u.socketId !== socket.id);
                    this.io.to(r).emit('user:update', { users: this.users[r] || [] });
                }
            }
        });

        socket.user_id = user_id;
        socket.roomKey = roomKey;
        socket.join(roomKey);

        const timer = this.rooms[roomKey];

        if (timer) {
            socket.emit('round:start', { roundId: timer.roundId, duration: timer.duration });
            socket.emit('timer:update', { time: timer.timeLeft });

            if (timer.timeLeft <= 5) {
                socket.emit('bet:close');
            }
        }

        if (!this.users[roomKey]) this.users[roomKey] = [];
        const exists = this.users[roomKey].find(u => u.user_id === user_id);
        if (!exists) {
            this.users[roomKey].push({ socketId: socket.id, user_id });
        }

        this.io.to(roomKey).emit('user:update', { users: this.users[roomKey] || [] });
    }

    handleDisconnect(socket) {
        const { roomKey } = socket;
        if (!roomKey || !this.users[roomKey]) return;

        this.users[roomKey] = this.users[roomKey].filter(u => u.socketId !== socket.id);

        this.io.to(roomKey).emit('user:update', { users: this.users[roomKey] || [] });
    }
}

module.exports = RoomManager;
