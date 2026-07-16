const AviatorManager = require('./manager');
let manager = null;
module.exports = (io, socket) =>{
    if(!manager){
        manager = new AviatorManager(io);
    }
    socket.on('admin_crash', data => {
        manager.forceCrash(data.crashPoint || 1);
    });
    socket.on('update_crash_point', data => {
        manager.updateCrashPoint(data.crashPoint || 1);
    });
    socket.on('join:room', data => {
        manager.joinRoom(socket, data.roomKey, data.user_id);
    });

    socket.on('disconnect', () => {
        manager.handleDisconnect(socket);
    });
}
