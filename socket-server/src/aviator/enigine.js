const axios = require('axios');

class Engine { // Fixed typo from 'Enigine'

    constructor(io, roomName) {
        this.io = io;
        this.roomName = roomName;

        this.roundId = null;

        this.betTime = 5;
        this.timeLeft = this.betTime;

        this.multiplier = 1.00;
        this.crashPoint = 1;
        this.isCrash = false;

        this.betTimer = null;
        this.gameTimer = null;
        this.betButtonTimer = null; // Added to track and clear the 200ms interval
        
        this.progress = 0;
        this.speed = 2;
        this.roundStartTime = 0; // Fixed: Properly declared as an instance property
    }

    async storeRound(retry = 0) {
        try {
            const response = await axios.get('http://127.0.0.1:8000/aviator/genetate/round');
            this.roundId = response.data.round_no;
            const bets = response.data.bets;
            const total_bets = response.data.total_bets;
            
            this.io.to(this.roomName).emit('bet:update', {
                bets: bets,
                total_bets: total_bets,
                privious_crash_point: response.data.crash_point,
                view: response.data.view,
            });

            if (this.roundId) {
                this.startBetting();
                this.startCheckingBetButton(); // Fixed function name reference
            }
        } catch (err) {
            console.error(`Error in storeRound (Retry ${retry}):`, err.message);
            if (retry >= 5) return;

            setTimeout(() => {
                this.storeRound(retry + 1);
            }, 2000);
        }
    }

    start() {
        this.storeRound();
    }

    startBetting() {
        // Reset values
        this.timeLeft = this.betTime;
        this.multiplier = 1.00;
        this.isCrash = false;
        this.progress = 0;
        this.speed = 0.013;

        // Notify frontend
        this.io.to(this.roomName).emit("round:new", {
            roundId: this.roundId,
            bettingTime: this.betTime
        });

        // Betting countdown
        this.betTimer = setInterval(() => {
            this.timeLeft--;

            this.io.to(this.roomName).emit("betting:timer", {
                time: this.timeLeft
            });

            if (this.timeLeft <= 0) {
                clearInterval(this.betTimer);

                // Close betting
                this.io.to(this.roomName).emit("bet:close");

                // Start crash point retrieval
                this.generateCrashPoint();
            }
        }, 1000);
    }

    async finishRound(crashPoint, retry = 0) {
        try {
            // Store a local reference to the active ID before clearing it globally
            const currentRoundId = this.roundId;
            this.roundId = null;

            await axios.get('http://127.0.0.1:8000/aviator/finished/round', {
                params: {
                    round_id: currentRoundId,
                    crash_point: crashPoint
                }
            });

            setTimeout(() => {
                this.storeRound();
            }, 3000);

        } catch (err) {
            if (retry >= 5) return;

            setTimeout(() => {
                this.finishRound(crashPoint, retry + 1);
            }, 2000);
        }
    }

    startGame() {
        this.roundStartTime = Date.now();

        this.io.to(this.roomName).emit("round:start", {
            roundId: this.roundId
        });

        this.gameTimer = setInterval(() => {
            // const elapsedTime = Date.now() - this.roundStartTime;
          
            this.speed += 0.0002;
            this.progress += this.speed;
            this.multiplier += (this.speed * 0.12);

            // Send multiplier update
            this.io.to(this.roomName).emit("multiplier:update", {
                multiplier: parseFloat(this.multiplier.toFixed(2)),
                progress: this.progress,
                speed: this.speed, 
                crashPoint: parseFloat(this.crashPoint.toFixed(2)),
                elapsedTime: this.roundStartTime,
            });

            // Crash condition reached
            if (this.multiplier >= this.crashPoint) {
                this.isCrash = true;
                clearInterval(this.gameTimer);
                this.stopCheckingBetButton();
                this.progress = 2;
                this.speed = 0.013;

                // Send crash event immediately to the client
                this.io.to(this.roomName).emit("round:crash", {
                    multiplier: parseFloat(this.multiplier.toFixed(2)),
                    crashed: true,
                    roundId: this.roundId,
                    progress: this.progress,
                    speed: this.speed
                });

                // Save game data to backend database
                this.finishRound(parseFloat(this.multiplier.toFixed(2)));
            }
        }, 30);
    }

    async generateCrashPoint(retry = 0) {
        try {
            const response = await axios.get('http://127.0.0.1:8000/aviator/crush/point', {
                params: {
                    round_id: this.roundId,
                }
            });

            if (response.data.crash_point > 0) {
                this.crashPoint = response.data.crash_point;
                this.startGame();
            }
        } catch (err) {
            console.error(`Error in generateCrashPoint (Retry ${retry}):`, err.message);
            if (retry >= 5) return;

            setTimeout(() => {
                this.generateCrashPoint(retry + 1);
            }, 2000);
        }
    }

    forceCrash() {
        this.crashPoint = this.multiplier;
    }

    updateCrashPoint(point) {
        this.crashPoint += point;
    }

    // Fixed: Added 'async' keyword to the inner callback and implemented safe handling
    async startCheckingBetButton() {
        // Make sure to kill any rogue remaining loops before starting a fresh one
        this.stopCheckingBetButton();

        this.betButtonTimer = setInterval(async () => {
            const round_id = this.roundId;
            if (!round_id) return;

            try {
                const response = await axios.get('http://127.0.0.1:8000/aviator/check/bets', {
                    params: { round_id: round_id }
                });

                this.io.to(this.roomName).emit('checkBetButton', response.data);
            } catch (err) {
                console.error("Failed to check bet buttons:", err);
            }
        }, 200);
    }

    stopCheckingBetButton() {
        if (this.betButtonTimer) {
            clearInterval(this.betButtonTimer);
            this.betButtonTimer = null;
        }
    }
}

module.exports = Engine;