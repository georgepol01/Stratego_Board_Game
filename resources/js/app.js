import { createApp } from 'vue';
import '/resources/css/app.css'; // Import the CSS file

const app = createApp({});

import HomeComponent from './components/HomeComponent.vue';
import GameBoardComponent from './components/GameBoardComponent.vue';

app.component('home-component', HomeComponent);
app.component('game-board-component', GameBoardComponent);


app.mount('#app');