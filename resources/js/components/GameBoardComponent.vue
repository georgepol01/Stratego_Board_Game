<template>
    <div
        v-if="isReadyToRender"
        id="board"
        class="flex flex-col gap-5 items-center justify-center h-full"
    >
        <div v-show="showTimer" class="mt-5">
            <div class="text-center text-3xl font-mono italic text-gray-500">
                Game starts in
            </div>
            <div
                class="text-center text-5xl italic font-light text-yellow-100 mt-2"
            >
                {{ timeLeft }}
            </div>
            <div
                class="timer-box mt-5"
                :style="{ width: timerWidth + '%' }"
            ></div>
        </div>
        <div v-show="!showTimer" class="mt-5">
            <div
                class="text-center text-3xl font-mono italic text-gray-500"
                :style="{ color: turnTextColor }"
            >
                {{ turnText }}
            </div>
        </div>
        <div
            :style="{ pointerEvents: gameFlag ? 'none' : 'auto' }"
            class="relative mt-7 rounded-lg"
        >
            <img
                src="/storage/boards/board.svg"
                class="w-full h-full object-contain rounded-lg"
            />
            <div
                :key="playerColor"
                class="absolute top-0 left-0 grid grid-cols-10 grid-rows-10 w-full h-full rounded-lg"
                :class="gridClass"
                style="
                    display: grid;
                    grid-template-columns: repeat(10, 1fr);
                    grid-template-rows: repeat(10, 1fr);
                "
            >
                <div
                    v-for="(piece, index) in filteredPieces"
                    :key="`piece-${index}`"
                    class="flex items-center justify-center rounded-lg"
                    :style="{
                        gridColumn: piece.position_x,
                        gridRow: piece.position_y,
                    }"
                    @click="handlePieceClick(piece)"
                    @mouseover="handlePieceHover(index)"
                    @mouseout="handlePieceHover(null)"
                >
                    <div
                        class="borderColor border rounded-lg"
                        :class="{
                            'no-border': shouldHideBorder(
                                piece.position_x,
                                piece.position_y
                            ),
                            hovered:
                                !shouldHideBorder(
                                    piece.position_x,
                                    piece.position_y
                                ) && index === hoveredPieceIndex,
                            'hightlight-previous-move-end':
                                swapFlag === 1 &&
                                clickedPiece &&
                                clickedPiece.position_x === piece.position_x &&
                                clickedPiece.position_y === piece.position_y,
                        }"
                    >
                        <div class="piece relative p-2 rounded-lg">
                            <img
                                v-if="isOwnPiece(piece)"
                                :src="getPieceImage(piece)"
                                :alt="`piece-${piece.id}`"
                                class="w-full h-full object-contain rounded-lg"
                            />
                            <img
                                v-if="
                                    !isOwnPiece(piece) &&
                                    shouldRenderPiece(piece)
                                "
                                :src="hiddenPiece()"
                                class="w-full h-full object-contain rounded-lg"
                            />
                            <div
                                v-if="
                                    !isOwnPiece(piece) &&
                                    !shouldRenderPiece(piece)
                                "
                                class="w-full h-full object-contain rounded-lg"
                            ></div>
                            <img
                                v-if="hasRank(piece) && isOwnPiece(piece)"
                                :src="getRankImage(piece)"
                                :alt="`rank-${piece.id}`"
                                class="absolute top-0 left-0 w-6 object-contain rounded-lg"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button
            v-show="gameFlag"
            @click="redirectToHome"
            class="game-over-message-button"
            :class="
                gameTurn == playerId
                    ? ' bg-green-700 hover:bg-green-500'
                    : ' bg-red-400 hover:bg-red-700'
            "
        >
            <h1 class="text-8xl p-5 font-mono text-yellow-100">
                {{ gameTurn == playerId ? "VICTORY" : "DEFEAT" }}
            </h1>
        </button>
    </div>
</template>

<script>
import axios from "axios";
import swapSound from "@/storage/assets/sounds/piece-sound.mp3";

export default {
    props: {
        gameId: {
            type: String,
            required: true,
        },
        playerId: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            gameFlag: false,
            showTimer: true,
            timeLeft: parseInt(localStorage.getItem("timeLeft")) || 120,
            timer: null,
            timerWidth:
                (parseInt(localStorage.getItem("timeLeft")) || 120) *
                (100 / 120),
            pieces: [],
            hoveredPieceIndex: null,
            swapSound: null,
            clickedPiece: null,
            targetPiece: null,
            swapFlag: null,
            playerColor: null,
            isDataLoaded: false,
            player1Id: null,
            player2Id: null,
            gameTurn: null,
            prevMoveStart: null,
            prevMoveEnd: null,
        };
    },

    async mounted() {
        this.shouldShowTimer();
        this.timerCall();
        this.swapSound = new Audio(swapSound);
        this.applyBackgroundColor();
        window.addEventListener("resize", this.applyBackgroundColor);
        await this.initializeGameBoard(this.gameId);
        await this.createBoard();
        this.setPieceColor();
        this.getPlayers();
        this.swapFlag = 0;
        await this.getGameTurn();
        this.pollIntervalId = setInterval(this.pollForGameStatusTurn, 4000);
    },

    beforeDestroy() {
        window.removeEventListener("resize", this.applyBackgroundColor);
        clearInterval(this.pollIntervalId);
    },

    methods: {
        redirectToHome() {
            const gamePageUrl = `/`;
            window.location.href = gamePageUrl;
        },

        async shouldShowTimer() {
            if (await this.getGameTime()) {
                this.showTimer = false;
            }
        },

        timerCall() {
            // Set a default value for timeLeft if not present in local storage
            if (!localStorage.getItem("timeLeft")) {
                localStorage.setItem("timeLeft", "120");
            }

            this.timeLeft = parseInt(localStorage.getItem("timeLeft"));

            this.timer = setInterval(() => {
                this.timeLeft--;
                this.timerWidth = (this.timeLeft / 120) * 100;

                localStorage.setItem("timeLeft", this.timeLeft.toString());

                if (this.timeLeft <= 0) {
                    this.showTimer = false;
                    clearInterval(this.timer);
                    localStorage.clear();
                }
            }, 1000);
        },

        playSwapSound() {
            this.swapSound.currentTime = 0;
            this.swapSound.play();
        },

        handlePieceHover(index) {
            this.hoveredPieceIndex = index;
        },

        applyBackgroundColor() {
            const wrapper = document.getElementById("app-wrapper");
            const windowHeight = window.innerHeight;
            if (wrapper) {
                wrapper.style.minHeight = `${windowHeight}px`;
            }
        },

        async initializeGameBoard(gameId) {
            try {
                const response = await axios.post(
                    `/api/game/init-board/${gameId}`
                );
            } catch (error) {
                console.error("Error initializing game board:", error);
            }
        },

        async createBoard() {
            try {
                const response = await axios.get(
                    `/api/game/create-game-board/${this.gameId}`
                );
                this.pieces = response.data;
            } catch (error) {
                console.error("Error creating game board:", error);
            }
        },

        setPieceColor() {
            axios
                .get(`/api/game/get-player-color/${this.playerId}`)
                .then((response) => {
                    this.playerColor = response.data.color;
                })
                .catch((error) => {
                    console.error("Error fetching player color:", error);
                });
        },

        async getGameTime() {
            try {
                const response = await axios.get(
                    `/api/game/get-game-time/${this.gameId}`
                );
                const gameTime = response.data.gameTime;
                const serverTime = response.data.serverTime;

                // Calculate the difference in seconds between serverTime and gameTime
                const timeDifference = Math.floor(
                    (new Date(serverTime) - new Date(gameTime)) / 1000
                );
                // Check if 120 seconds have passed
                return timeDifference > 120;
            } catch (error) {
                console.error("Error fetching game time:", error);
                // Return false in case of an error
                return false;
            }
        },

        async getGameTurn() {
            try {
                const response = await axios.get(
                    `/api/game/get-game-turn/${this.gameId}`
                );
                this.gameTurn = response.data.gameTurn;
            } catch (error) {
                console.error("Error fetching game turn:", error);
            }
        },

        getPlayers() {
            axios
                .get(`/api/game/get-players/${this.gameId}`)
                .then((response) => {
                    this.player1Id = response.data.player1Id;
                    this.player2Id = response.data.player2Id;
                    this.isDataLoaded = true;
                })
                .catch((error) => {
                    console.error("Error fetching players:", error);
                });
        },

        isOwnPiece(piece) {
            return piece.color === this.playerColor;
        },

        shouldRenderPiece(piece) {
            return piece && piece.type != null && piece.status == "active";
        },

        shouldHideBorder(positionX, positionY) {
            // Check if the border should be hidden for specific grid areas
            return (
                // Specific grid areas (5,3), (5,4), (5,7), (5,8), (6,3), (6,4), (6,7), (6,8)
                (positionX === 3 && positionY === 5) ||
                (positionX === 4 && positionY === 5) ||
                (positionX === 3 && positionY === 6) ||
                (positionX === 4 && positionY === 6) ||
                (positionX === 7 && positionY === 5) ||
                (positionX === 8 && positionY === 5) ||
                (positionX === 7 && positionY === 6) ||
                (positionX === 8 && positionY === 6)
            );
        },

        getPieceImage(piece) {
            if (piece && piece.type) {
                return `/storage/pieces/${piece.type.toLowerCase()}-${
                    piece.color
                }.svg`;
            }
        },

        hiddenPiece() {
            return `/storage/pieces/hidden-${this.playerColor}.svg`;
        },

        hasRank(piece) {
            // Exclude flag and bomb from having a rank
            return (
                [
                    "spy",
                    "scout",
                    "miner",
                    "sergeant",
                    "lieutenant",
                    "captain",
                    "major",
                    "colonel",
                    "general",
                    "marshal",
                ].includes(piece.type) && !["flag", "bomb"].includes(piece.type)
            );
        },

        getRankImage(piece) {
            if (piece) {
                return `/storage/pieces/ranks/rank-${this.getRankNumber(
                    piece
                )}-${piece.color}.svg`;
            }
        },

        getRankNumber(piece) {
            // Map piece types to corresponding ranks
            const rankMap = {
                spy: "s",
                scout: "9",
                miner: "8",
                sergeant: "7",
                lieutenant: "6",
                captain: "5",
                major: "4",
                colonel: "3",
                general: "2",
                marshal: "1",
                flag: "",
                bomb: "",
            };

            return rankMap[piece.type] || "0"; // Default to '0' if the type is not found in the map
        },

        calculateDistance(clickedPiece, targetPiece) {
            if (clickedPiece.type !== "scout") {
                const distanceX = Math.abs(
                    clickedPiece.position_x - targetPiece.position_x
                );
                const distanceY = Math.abs(
                    clickedPiece.position_y - targetPiece.position_y
                );

                const isAdjacent =
                    (distanceX === 1 && distanceY === 0) ||
                    (distanceX === 0 && distanceY === 1);

                return isAdjacent;
            } else {
                const isScoutValid =
                    (clickedPiece.position_x === targetPiece.position_x &&
                        this.noPiecesInBetween(
                            clickedPiece,
                            targetPiece,
                            "vertical"
                        )) ||
                    (clickedPiece.position_y === targetPiece.position_y &&
                        this.noPiecesInBetween(
                            clickedPiece,
                            targetPiece,
                            "horizontal"
                        ));

                return isScoutValid;
            }
        },

        noPiecesInBetween(clickedPiece, targetPiece, axis) {
            const start =
                axis === "vertical"
                    ? Math.min(clickedPiece.position_y, targetPiece.position_y)
                    : Math.min(clickedPiece.position_x, targetPiece.position_x);
            const end =
                axis === "vertical"
                    ? Math.max(clickedPiece.position_y, targetPiece.position_y)
                    : Math.max(clickedPiece.position_x, targetPiece.position_x);

            // Check if there are no pieces in between the start and end positions
            for (let i = start + 1; i < end; i++) {
                const position =
                    axis === "vertical"
                        ? { position_x: clickedPiece.position_x, position_y: i }
                        : {
                              position_x: i,
                              position_y: clickedPiece.position_y,
                          };
                if (this.isPieceAtPosition(position)) {
                    return false;
                }
            }

            return true;
        },

        isPieceAtPosition(position) {
            // Check if there is a piece at the specified position
            return this.pieces.some(
                (piece) =>
                    piece.position_x === position.position_x &&
                    piece.position_y === position.position_y
            );
        },

        isAttackValid(attacker, defender) {
            const rankMap = {
                flag: 0,
                spy: 1,
                scout: 2,
                miner: 3,
                sergeant: 4,
                lieutenant: 5,
                captain: 6,
                major: 7,
                colonel: 8,
                general: 9,
                marshal: 10,
                bomb: 11,
            };

            const attackerRank = rankMap[attacker];
            const defenderRank = rankMap[defender];

            if (defender == "flag") {
                // If there is a valid attack on a flag, the game is over
                return "game_over";
            }
            // Extra rule: If pieces have the same rank, both are removed
            if (attackerRank == defenderRank) {
                return "draw";
            }
            // Check specific rules
            if (attacker == "miner" && defender == "bomb") {
                // Miner: Can defuse Bombs
                return "won";
            } else if (attacker == "spy" && defender == "marshal") {
                // Spy: Can capture Marshal
                return "won";
            } else {
                // Other pieces: Compare ranks
                return attackerRank > defenderRank ? "won" : "lost";
            }
        },

        async handlePieceClick(clickedPiece) {
            // Check and handle piece board setup for each player before the game starts
            if (!(await this.getGameTime())) {
                if (this.isOwnPiece(clickedPiece) && this.swapFlag == 0) {
                    this.clickedPiece = clickedPiece;
                    this.swapFlag = 1;
                } else if (
                    this.isOwnPiece(clickedPiece) &&
                    this.swapFlag == 1
                ) {
                    this.targetPiece = clickedPiece;
                    this.swapFlag = 2;
                }
                if (this.swapFlag == 2) {
                    const swapData = {
                        pieces: [
                            {
                                id: this.clickedPiece.id,
                                position_x: this.targetPiece.position_x,
                                position_y: this.targetPiece.position_y,
                            },
                            {
                                id: this.targetPiece.id,
                                position_x: this.clickedPiece.position_x,
                                position_y: this.clickedPiece.position_y,
                            },
                        ],
                    };

                    this.updateGamePieces(swapData);
                    this.playSwapSound();
                    this.clickedPiece = null;
                    this.targetPiece = null;
                    this.swapFlag = 0;
                }
            }
            //Check and handle piece board movement for each player after game starts
            else if (this.gameTurn == this.playerId) {
                if (this.isOwnPiece(clickedPiece) && this.swapFlag == 0) {
                    this.clickedPiece = clickedPiece;
                    this.swapFlag = 1;
                } else if (
                    !this.isOwnPiece(clickedPiece) &&
                    this.swapFlag == 1 &&
                    !this.shouldHideBorder(
                        clickedPiece.position_x,
                        clickedPiece.position_y
                    )
                ) {
                    this.targetPiece = clickedPiece;
                    this.swapFlag = 2;
                }
                if (this.swapFlag == 2) {
                    //mMove to empty space
                    if (this.targetPiece.type == null) {
                        if (
                            this.clickedPiece.type != "bomb" &&
                            this.clickedPiece.type != "flag" &&
                            this.calculateDistance(
                                this.clickedPiece,
                                this.targetPiece
                            )
                        ) {
                            const uppdateData = {
                                pieces: [
                                    {
                                        id: this.clickedPiece.id,
                                        position_x: this.targetPiece.position_x,
                                        position_y: this.targetPiece.position_y,
                                    },
                                ],
                            };

                            this.updateGamePieces(uppdateData);
                            this.gameTurn =
                                this.playerId == this.player1Id
                                    ? this.player2Id
                                    : this.player1Id;
                            this.playSwapSound();
                            this.updateGameTurnOnServer(this.gameTurn);
                        }
                        this.clickedPiece = null;
                        this.targetPiece = null;
                        this.swapFlag = 0;
                    }
                    // Move own piece to attack
                    else if (this.targetPiece.color != this.playerColor) {
                        if (
                            this.clickedPiece.type != "bomb" &&
                            this.clickedPiece.type != "flag" &&
                            this.calculateDistance(
                                this.clickedPiece,
                                this.targetPiece
                            )
                        ) {
                            const battleResult = this.isAttackValid(
                                this.clickedPiece.type,
                                this.targetPiece.type
                            );

                            let battleData = {
                                pieces: [],
                            };

                            if (battleResult == "draw") {
                                battleData = {
                                    pieces: [
                                        {
                                            id: this.clickedPiece.id,
                                            position_x: 0,
                                            position_y: 0,
                                        },
                                        {
                                            id: this.targetPiece.id,
                                            position_x: 0,
                                            position_y: 0,
                                        },
                                    ],
                                };
                            } else if (battleResult == "won") {
                                battleData = {
                                    pieces: [
                                        {
                                            id: this.clickedPiece.id,
                                            position_x:
                                                this.targetPiece.position_x,
                                            position_y:
                                                this.targetPiece.position_y,
                                        },
                                        {
                                            id: this.targetPiece.id,
                                            position_x: 0,
                                            position_y: 0,
                                        },
                                    ],
                                };
                            } else if (battleResult == "lost") {
                                battleData = {
                                    pieces: [
                                        {
                                            id: this.clickedPiece.id,
                                            position_x: 0,
                                            position_y: 0,
                                        },
                                    ],
                                };
                            } else if (battleResult == "game_over") {
                                this.gameFlag = true;
                                this.clearGame();
                                return;
                            }

                            this.updateGamePieces(battleData);
                            this.gameTurn =
                                this.playerId == this.player1Id
                                    ? this.player2Id
                                    : this.player1Id;
                            this.playSwapSound();
                            this.updateGameTurnOnServer(this.gameTurn);
                        }
                        this.clickedPiece = null;
                        this.targetPiece = null;
                        this.swapFlag = 0;
                    }
                }
            }
        },

        async clearGame() {
            try {
                const response = await axios.post(
                    `/api/game/clear-game/${this.gameId}`
                );

                if (response.status >= 200 && response.status < 300) {
                } else {
                    console.error(
                        "Error clearing game. Server responded with:",
                        response.status,
                        response.data
                    );
                }
            } catch (error) {
                console.error("Error clearing game:", error);
            }
        },

        async updateGamePieces(data) {
            try {
                const response = await axios.post(
                    `/api/game/update-game-pieces/${this.gameId}`,
                    data
                );
                if (response.data.updatedPieces !== undefined) {
                    // Create a new array and assign it to this.pieces
                    this.pieces = [...response.data.updatedPieces];
                } else {
                    console.error("Updated pieces are undefined:", response);
                }
            } catch (error) {
                console.error("Error updating game pieces:", error);
            }
        },

        async updateGameTurnOnServer(newGameTurn) {
            try {
                // Send the new gameTurn to the backend
                await axios.post(`/api/game/set-game-turn/${this.gameId}`, {
                    gameTurn: newGameTurn,
                });
            } catch (error) {
                console.error("Error updating game turn on server:", error);
            }
        },

        async pollForGameStatusTurn() {
            try {
                const response = await axios.get(
                    `/api/game/get-game-turn/${this.gameId}`
                );
                const newGameTurn = response.data.gameTurn;
                const gameFinished = response.data.gameFinished;

                if (gameFinished) {
                    // Set gameFlag to true if the game is finished
                    this.gameFlag = true;
                } else if (newGameTurn !== this.gameTurn) {
                    // Update gameTurn in the component
                    this.gameTurn = newGameTurn;

                    // Send the new gameTurn to the backend to update the database
                    await this.updateGameTurnOnServer(newGameTurn);

                    if (this.gameTurn == this.playerId) {
                        this.createBoard();
                    }
                }
            } catch (error) {
                console.error("Error fetching game turn:", error);
            }
        },
    },

    computed: {
        filteredPieces() {
            const allPositions = [];

            // Generate all possible positions on the 10x10 grid
            for (let x = 1; x <= 10; x++) {
                for (let y = 1; y <= 10; y++) {
                    allPositions.push({ position_x: x, position_y: y });
                }
            }

            // Check if this.pieces is defined before filtering
            if (!this.pieces) {
                console.warn("this.pieces is undefined");
                return [];
            }

            // Filter pieces based on shouldRenderPiece condition
            const renderedPieces = this.pieces.filter((piece) =>
                this.shouldRenderPiece(piece)
            );

            // Combine rendered pieces with empty positions
            const allPieces = renderedPieces.concat(
                allPositions.filter((position) => {
                    return !renderedPieces.some(
                        (piece) =>
                            piece.position_x === position.position_x &&
                            piece.position_y === position.position_y
                    );
                })
            );

            return allPieces;
        },

        isReadyToRender() {
            return this.isDataLoaded && this.playerColor !== null;
        },

        gridClass() {
            return {
                "player-red":
                    this.playerId == this.player1Id &&
                    this.playerColor == "red",
                "player-blue":
                    this.playerId == this.player1Id &&
                    this.playerColor == "blue",
            };
        },

        turnText() {
            return this.gameTurn == this.playerId
                ? "Your Turn"
                : "Opponent's Turn";
        },
        turnTextColor() {
            return this.gameTurn == this.playerId
                ? this.playerColor
                : this.opponentColor;
        },
        opponentColor() {
            return this.playerColor == "red" ? "blue" : "red";
        },
    },
};
</script>

<style scoped>
.player-red {
    transform: rotate(180deg);
}

.player-red .piece {
    transform: rotate(180deg);
}

.player-blue {
    transform: rotate(180deg);
}

.player-blue .piece {
    transform: rotate(180deg);
}

.no-border {
    border: none;
}

.borderColor {
    border-color: rgb(22, 19, 10);
    width: 100px;
    height: 100px;
}

.hovered {
    background-color: rgb(17, 70, 20);
    opacity: 0.3;
}

.hightlight-previous-move-end {
    background-color: rgb(205, 218, 26);
    opacity: 0.3;
}

.timer-box {
    width: 1000px;
    height: 5px;
    background-color: #5b645c;
    border-radius: 6px; /* Add border-radius for rounded corners */
    transition: width 1s ease; /* Add a 1-second transition for smooth width changes */
}

.game-over-message-button {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    border-radius: 10px;
}

button {
    cursor: url("/storage/icons/custom-cursor.cur"), auto;
}
</style>
