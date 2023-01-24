<?php include "../head.php";
Guard::init()->CommandRequired();
?>
<div class="grid new" id="root" v-cloak>
    <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new"><?= Config::$name; ?> Factions</h1>
        <div id="staff" class="selectionPanel">
            <div v-for="(u, k) in faction_players" @click="loadFullPlayer(u.id, k)" class="selectionTab">
                <span class="right-info">{{u.rank}}</span>
                <span style="font-size: 25px;">{{displayName(k)}}</span>
            </div>
        </div>
    </div>
    <div class="grid__col grid__col--4-of-6">
        <div class="infoPanelContainer">
            <div v-if="!currentPlayerOpen" class="infoPanel">
                <h1>Select A User</h1>
            </div>
            <div v-else id="case_info" class="infoPanel">
                <h2>
                    <small class="small-txt" v-if="current_player.isPD || current_player.isEMS">{{current_player.faction_rank}}</small>
                    {{current_player.displayName}}
                </h2>
                <div v-if="current_player.isPD" class="field">
                    <div class="fieldTitle">Police Rank</div>
                    <select class="fieldSelector" v-model="current_player.faction_rank_real" @change="savePlayerRank">
                        <option v-for="(val, key) in factionConfig.police" :value="key">{{val}}</option>
                    </select>
                </div>
                <div v-else-if="current_player.isEMS" class="field">
                    <div class="fieldTitle">Medic Rank</div>
                    <select class="fieldSelector" v-model="current_player.faction_rank_real" @change="savePlayerRank">
                        <option v-for="(val, key) in factionConfig.medic" :value="key">{{val}}</option>
                    </select>
                </div>
                <div v-else class="btnGroup">
                    <button @click="makePolice">Make {{current_player.displayName}} Police</button>
                    <button @click="makeMedic">Make {{current_player.displayName}} Medic</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    let vm = new Vue({
        el: '#root',
        data: {
            faction_players: [
                {id: 0, first_name: 'Loading...', last_name: ''}
            ],
            current_player: {},
            currentPlayerOpen: false,
            factionConfig: []
        },
        mounted() {
            this.loadPlayerList();
            this.loadConfig();

            let hash = ['default'];
            hash = window.location.hash.substring(1).split(':');
            console.log(hash);
            switch (hash[0]) {
                case "player":
                    this.loadFullPlayer(hash[1]);
                    break;
            }
        },
        methods: {
            loadPlayerList() {
                $.get('/api/v1/factionPlayerList', data => {
                    data = JSON.parse(data);

                    if (data.code === 200) {
                        this.faction_players = data.response;
                    }
                });
            },
            loadFullPlayer(id, k) {
                $.get('/api/v1/factionPlayerFull', {'id': id}, data => {
                    data = JSON.parse(data);

                    if (data.code === 200) {
                        this.current_player = data.response;
                        this.current_player.list_key = k;
                        this.currentPlayerOpen = true;
                    }
                });
            },
            displayName(key) {
                return this.faction_players[key].first_name + ' ' + this.faction_players[key].last_name;
            },
            loadConfig() {
                $.get('/api/v1/factionConfigs', data => {
                    this.factionConfig = JSON.parse(data);
                });
            },
            savePlayerRank() {
                $.post('/api/v1/factionSaveRank', {
                    playerID: this.current_player.id,
                    rank: this.current_player.faction_rank_real
                }, data => {
                    data = JSON.parse(data);

                    if (data.code === 200) {
                        this.current_player.faction_rank = data.response.faction_rank;
                        this.faction_players[this.current_player.list_key].rank = data.response.faction_rank;
                    } else {
                        new Noty({
                            type: 'warning',
                            text: data.message,
                            timeout: 4000
                        }).show();
                    }
                });
            },
            makeMedic() {
                $.post('/api/v1/factionInitializePlayer', {
                    type: 'medic',
                    playerID: this.current_player.id
                }, data => {
                    data = JSON.parse(data);

                    if (data.code === 200) {
                        this.faction_players.push({
                            faction_rank: data.response.faction_rank_real,
                            rank: data.response.faction_rank,
                            id: data.response.id,
                            isEMS: data.response.isEMS,
                            isPD: data.response.isPD,
                            last_name: data.response.lastName,
                            first_name: data.response.firstName,
                            username: data.response.username
                        });

                        this.current_player = data.response;
                        this.current_player.list_key = this.faction_players.length - 1;
                    } else {
                        new Noty({
                            type: 'warning',
                            text: data.message,
                            timeout: 4000
                        }).show();
                    }
                });
            },
            makePolice() {
                $.post('/api/v1/factionInitializePlayer', {
                    type: 'police',
                    playerID: this.current_player.id
                }, data => {
                    data = JSON.parse(data);

                    if (data.code === 200) {
                        this.faction_players.push({
                            faction_rank: data.response.faction_rank_real,
                            rank: data.response.faction_rank,
                            id: data.response.id,
                            isEMS: data.response.isEMS,
                            isPD: data.response.isPD,
                            last_name: data.response.lastName,
                            first_name: data.response.firstName,
                            username: data.response.username
                        });

                        this.current_player = data.response;
                        this.current_player.list_key = this.faction_players.length - 1;
                    } else {
                        new Noty({
                            type: 'warning',
                            text: data.message,
                            timeout: 4000
                        }).show();
                    }
                });
            }
        },
        computed: {
            getCurrentPlayerFaction() {
                return (this.current_player.isPD) ? 'Police Officer' : 'Medic';
            }
        }
    })
</script>
<style>
    .inline-button {
        display: inline-block;
        margin: 0 0 0 10px;
    }

    .right-info {
        float: right;
        vertical-align: top;
        font-size: 12px;
    }

    .small-txt {
        font-size: 16px;
        vertical-align: middle;
    }
</style>