<template>
    <div id="home" class="flex justify-center h-full">
        <div class="flex flex-col gap-3 mt-8 items-center">
            <img src="storage/icons/logo.png" alt="logo" />
            <div
                v-show="loading"
                class="flex flex-col gap-5 justify-center items-center mb-5 text-3xl text-gray-400"
            >
                <img
                    src="storage/icons/loading.gif"
                    alt="loading"
                    class="w-10"
                />
                <p>Searching for an opponent...</p>
            </div>
            <div
                v-show="loadingDis"
                class="flex flex-col gap-5 items-center justify-center"
            >
                <p class="text-3xl font-mono text-yellow-100">
                    Enter your name
                </p>
                <input
                    class="w-60 h-10 p-1 bg-gray-400 rounded-lg"
                    v-model="name"
                    type="text"
                    autocomplete="off"
                />
                <button
                    @click="startGameAndPlaySound"
                    class="px-8 py-4 font-bold mt-16 rounded-full text-lg text-gray-300 bg-green-700 hover:bg-green-500"
                >
                    PLAY
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import btnSound from "@/storage/assets/sounds/button-sound.mp3";

export default {
    data() {
        return {
            btnSound: null,
            name: "",
            loading: false,
            loadingDis: true,
            gameId: null,
            playerId: null,
        };
    },

    mounted() {
        this.btnSound = new Audio(btnSound);
        this.applyBackgroundColor();
        window.addEventListener("resize", this.applyBackgroundColor);
    },

    beforeDestroy() {
        window.removeEventListener("resize", this.applyBackgroundColor);
    },

    methods: {
        async startGameAndPlaySound() {
            this.playBtnSound();
            this.startGame();
        },

        playBtnSound() {
            this.btnSound.currentTime = 0;
            this.btnSound.play();
        },

        applyBackgroundColor() {
            const wrapper = document.getElementById("app-wrapper");
            const windowHeight = window.innerHeight;
            if (wrapper) {
                wrapper.style.minHeight = `${windowHeight}px`;
            }
        },

        async startGame() {
            if (this.name.trim() === "") {
                alert("Please enter your name to continue.");
                return;
            }
            // Show loading spinner
            this.loading = true;
            this.loadingDis = false;

            try {
                // Send player name to server and handle the response
                const response = await axios.post("/api/game/start", {
                    name: this.name,
                });
                // Assuming the server responds with the game ID and status
                this.gameId = response.data.gameId;
                this.playerId = response.data.playerId;
                const gameStatus = response.data.status;
                // Check if the game status is 'playing' before redirecting
                if (gameStatus === "playing") {
                    // Redirect to the game page with the obtained game ID
                    this.redirectToGamePage(this.gameId, this.playerId);
                } else {
                    // Set the gameId property and start polling for game status
                    this.gameId = this.gameId;
                    this.playerId = this.playerId;
                    this.pollForGameStatus();
                }
            } catch (error) {
                console.error("Error starting the game:", error);
                // Handle the error as needed
            }
        },

        async pollForGameStatus() {
            // Polling interval (e.g., every 5 second)
            const pollingInterval = 5000;
            // Poll for status change until it becomes 'playing'
            const intervalId = setInterval(async () => {
                try {
                    const response = await axios.get(
                        `/api/game/status/${this.gameId}`
                    );
                    const gameStatus = response.data.status;

                    if (gameStatus === "playing") {
                        // Stop polling and redirect to the game page
                        clearInterval(intervalId);
                        this.redirectToGamePage(this.gameId);
                        // Hide loading spinner
                        this.loading = false;
                        this.loadingDis = true;
                    }
                } catch (error) {
                    console.error("Error polling for game status:", error);
                }
            }, pollingInterval);
        },

        redirectToGamePage(gameId) {
            const gamePageUrl = `/game/${gameId}/${this.playerId}`;
            window.location.href = gamePageUrl;
        },
    },
};
</script>

<style scoped>
button {
    cursor: url("/storage/icons/custom-cursor.cur"), auto;
}
</style>
