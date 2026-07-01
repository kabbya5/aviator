const http = require('http');
const {Server} = require('socket.io');

const roomEvents = require('./roomEvents');
const aviatorEvents = require('./aviator/events');

const server = http.createServer((req, res) => {
    res.writeHead(200, { 'Content-Type': 'text/plain' });
    res.end('Socket server is running\n');
});


const io = new Server(server, {
    cors: {
        origin: ["https://boomx.club", "http://127.0.0.1:8000",'http://localhost'],
        methods: ["GET", "POST"],
        credentials: true
    },
    path: "/socket"
});

io.on('connection', socket => {
    roomEvents(io, socket);
    aviatorEvents(io, socket);
});

io.on("connection_error", (err) => {
    console.log("Socket connection error:", err);
});

const PORT = 3000;

server.listen(PORT, () => {
    console.log('Socket server running on port 3000');
})
