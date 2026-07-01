const axios = require('axios');

class Enigine {

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
        this.progress = 0;
        this.speed = 2;
    }

    async storeRound(retry = 0) {
        try {
            const response = await axios.get('http://127.0.0.1:8000/aviator/genetate/round');
            this.roundId = response.data.round_no;
            const bets = response.data.bets;
            const total_bets = response.data.total_bets;
            this.io.to(this.roomName).emit('bet:update',{
                bets:bets,
                total_bets:total_bets,
            });

            if(this.roundId){
                this.startBetting();
            }
        }catch (err) {
            if (retry >= 5) {
                return;
            }

            setTimeout(() => {
                this.storeRound(retry + 1);
            }, 2000);
        }
    }

    start() {
        this.storeRound();
    }

    startBetting() {
        // reset values
        this.timeLeft = this.betTime;
        this.multiplier = 1.00;
        this.isCrash = false;
        this.progress = 0;
        this.speed = 0.002;

        // notify frontend
        this.io.to(this.roomName).emit("round:new", {
            roundId: this.roundId,
            bettingTime: this.betTime
        });

        // betting countdown
        this.betTimer = setInterval(() => {

            this.timeLeft--;

            this.io.to(this.roomName).emit("betting:timer", {
                time: this.timeLeft
            });

            if (this.timeLeft <= 0) {

                clearInterval(this.betTimer);

                // close betting
                this.io.to(this.roomName).emit("bet:close");

                // start crash point
                this.generateCrashPoint()
            }

        }, 1000);
    }

    async finishRound(crashPoint, retry = 0) {
        try {
            const response = await axios.get('http://127.0.0.1:8000/aviator/finished/round', {
                params: {
                    round_id: this.roundId,
                    crash_point: crashPoint
                }
            });

            this.roundId = null;

            setTimeout(() => {
                this.storeRound();
            }, 3000);

        } catch (err) {

            console.error(err);

            if (retry >= 5) {
                return;
            }

            setTimeout(() => {
                this.finishRound(crashPoint, retry + 1);
            }, 2000);
        }
    }

    startGame() {
        this.io.to(this.roomName).emit("round:start", {
            roundId: this.roundId
        });

        this.gameTimer = setInterval(() => {

            // increase multiplier
            this.speed += 0.0002;

            this.progress += this.speed;

            this.multiplier += (
                this.speed * 1.4
            );

            // send multiplier update
            this.io.to(this.roomName).emit("multiplier:update", {
                multiplier: parseFloat(this.multiplier.toFixed(2)),
                progress: this.progress,
                speed: this.speed
            });

            // crash
            if (this.multiplier >= this.crashPoint) {
                this.isCrash = true;
                clearInterval(this.gameTimer);
                this.progress = 0;
                this.speed = 0.002;

                this.finishRound(this.multiplier);

                this.io.to(this.roomName).emit("round:crash", {
                    multiplier: this.multiplier,
                    crashed: true,
                    roundId: this.roundId,
                    progress: this.progress,
                    speed: this.speed
                });
            }

        }, 100);
    }

    async generateCrashPoint(retry = 0) {
        try {
            const response = await axios.get('http://127.0.0.1:8000/aviator/crush/point',{
                params: {
                    round_id: this.roundId,
                }
            });

            if(response.data.crash_point > 0){
                this.crashPoint = response.data.crash_point;
                this.startGame();
            }

        } catch (err) {

            if (retry >= 5) {
                return;
            }

            setTimeout(() => {
                this.generateCrashPoint( retry + 1);
            }, 2000);
        }
    }
}

module.exports = Enigine;
