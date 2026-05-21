const axios = require('axios');

class TimerEngine {
    constructor(io, roomKey, duration, baseDuration = 300) {
        this.io = io;
        this.roomKey = roomKey;
        this.duration = duration;
        this.baseDuration = baseDuration;

        this.timeLeft = duration;
        this.roundId = null;

        this.interval = null;

        this.state = 'idle';
        this.resultCalled = false;
        this.betClosed = false;

        this.isRunning = false;
    }

    async storeRound() {
        try {
            const response = await axios.get(
                'https://boomx.club/wingo/generate/round',
                {
                    params: {
                        wingo_slug: this.roomKey,
                    }
                }
            );

            return response.data.round_no;

        } catch (err) {
            await new Promise(res => setTimeout(res, 2000));
            return await this.storeRound();
        }
    }

    async start() {

        if (this.isRunning) return;
        this.isRunning = true;

        this.roundId = await this.storeRound();

        if (!this.roundId) {
            this.isRunning = false;
            setTimeout(() => this.start(), 2000);
            return;
        }

        this.interval = setInterval(async () => {

            const now = Math.floor(Date.now() / 1000);
            const baseTime = now % this.baseDuration;
            const offset = baseTime % this.duration;

            let timeLeft = this.duration - offset;
            if (timeLeft === this.duration) timeLeft = 0;
            if (isNaN(timeLeft)) timeLeft = this.duration;

            this.io.to(this.roomKey).emit('timer:update', { time: timeLeft });


            if (timeLeft === 5) {
                this.io.to(this.roomKey).emit('bet:close', { time: timeLeft });
            }

            if (timeLeft === 2 && !this.resultCalled) {
                this.resultCalled = true;

                try {
                    await axios.get('https://boomx.club/wingo/result', {
                        params: { round_id: this.roundId }
                    });
                } catch (err) {
                    console.log('Result generate failed', err);
                }
            }

            if (timeLeft === 0) {

                this.io.to(this.roomKey).emit('round:end', {
                    roundId: this.roundId
                });


                this.resultCalled = false;
                this.betClosed = false;


                this.roundId = await this.storeRound();

                this.io.to(this.roomKey).emit('round:start', {
                    roundId: this.roundId,
                    duration: this.duration
                });
            }

        }, 1000);
    }
}

module.exports = TimerEngine;
