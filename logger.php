<?php session_start();
include "head.php";
Guard::init()->StaffRequired();
?>
<div class="grid new" id="root" v-cloak style="padding-left:15px;z-index: 25;">
    <div class="grid__col grid__col--1-of-6" style="box-shadow: 0 0 5px 0 rgba(0,0,0,0.2);">
        <div class="selectionPanel new"
             style="margin-left: 5px;max-width: 100%;background-color: #1c1b30;height: 100vh;overflow: auto;">
            <p class="label" style="text-align: center;"><?= Config::$name; ?> Staff</p>
            <p class="label">Case Tools</p>
            <button class="pickbtn" id="addOtherStaff" @click="addAssistantStaffMember()" disabled>Add Staff</button>
            <button class="pickbtn" id="addPlayerReporter" @click="addPlayer()">Add Player</button>
            <button class="pickbtn" id="TypeOfReportButton" style="position: relative;text-align: left;"
                    :class="{ open: menus.reportType.isOpen }" @click="toggleList('reportType', 'TypeOfReportList')">
                Report Type: {{ menus.reportType.current }} <i id="torangle" style="position: absolute;right: 10px;"
                                                               :class="{ open: menus.reportType.isOpen }"
                                                               class="ts2 fas fa-angle-down"></i></button>
            <div class="reportTypes" id="TypeOfReportList">
                <button class="pickbtn submenuBtn" @click="selectReportType('Other')">Other</button>
                <button class="pickbtn submenuBtn" @click="selectReportType('Report')">Report</button>
                <button class="pickbtn submenuBtn" @click="selectReportType('General Support')">General Support</button>
                <button class="pickbtn submenuBtn" @click="selectReportType('General Tags')">General Tags</button>
                <button class="pickbtn submenuBtn" @click="selectReportType('Whitelisting')">Whitelisting</button>
                <button class="pickbtn submenuBtn" @click="selectReportType('Compensation')">Compensation</button>
                <button class="pickbtn submenuBtn" @click="selectReportType('Ban Log')">Ban Log</button>
                <button class="pickbtn submenuBtn" @click="selectReportType('Unban Log')">Unban Log</button>
            </div>
            <p class="label">Punishments</p>
            <button class="pickbtn" id="modalLaunch" launch="addPunishment">Add Punishment Report</button>
            <button class="pickbtn" id="modalLaunch" launch="addBan">Add Ban Report</button>
        </div>
    </div>
    <div class="grid__col grid__col--5-of-6" style="height: 100vh !important;overflow: auto;z-index: 0;">
        <div class="infoPanelContainer">
            <div class="infoPanel">
                <div class="field">
                    <div class="fieldTitle" id="doeTitle">Description Of Events <span id="doeTitleWords"
                                                                                      style="color: #555;">({{CountDOE}} Words)</span>
                    </div>
                    <textarea class="fieldTextarea" id="doi" placeholder="Description Of The Events*"
                              v-model="info.description"></textarea>
                </div>
                <input id="lsm" type="hidden" value="<?= $user->info->username; ?>">
                <input id="typeOfReportField" type="hidden" value="Other">
                <div id="otherStaffList">
                    <div v-for="(val, key) in assistantStaff" class="field">
                        <div class="fieldTitle">Assistant Staff #{{key+1}} <i @click="removeAssistantStaff(key)"
                                                                                     style="color: #aaaaaa;float: right;cursor: pointer;"
                                                                                     class="fas fa-trash"></i></div>
                        <select class="fieldSelector" id="os1" v-model="assistantStaff[key].selected">
                            <option value="0" @input="updateAssistant(key, 0)">Select A Staff Member</option>
                            <option v-for="(staff_val, staff_key) in staff_list" :value="staff_val.name">
                                {{staff_val.display}}
                            </option>
                        </select>
                    </div>
                </div>
                <div id="playerList">
                    <div v-for="(val, key) in playersInvolved" class='field'>
                        <div class='fieldTitle'>Player Involved #{{key+1}} <select style="margin-bottom: 0;" type="text"
                                                                                   v-model="val.type">
                                <option value="0">Reporter</option>
                                <option value="1">Reported</option>
                            </select>
                            <i v-if="key > 0" @click="removePlayer(key)"
                               style="color: #aaaaaa;float: right;cursor: pointer;"
                               class="fas fa-trash"></i></div>
                        <input v-model='val.name' class='fieldInput' :placeholder='playerTypes[val.type] + "&apos;s Name"'>
                        <input v-model='val.uuid' class='fieldInput' placeholder='Player UUID (Steam or BattleEye)'>
                    </div>
                </div>
                <div class="punishmentsAndBans">
                    <div v-for="p in reports.added.punishments">
                        <div class='punishment_report logger'><span
                                    class='player'>{{p.player}}'s Punishment Report</span>
                            <span class='points'>{{p.points}} Points</span>
                            <p class='rules'>Rules Broken: {{p.rules}}</p>
                            <p class='comments'>Staff Comment: {{p.comments}}</p>
                        </div>
                    </div>
                    <div v-for="b in reports.added.bans">
                        <div class='punishment_report logger'>
                            <span class='player'>{{b.player.name}}'s Ban Report</span>
                            <span class='points'>{{getBanLength(b.length)}}</span>
                            <div style='padding: 10px 0 0;'>
                                <span class='typeofreport'>Teamspeak Ban: {{b.teamspeak}}</span>
                                <span class='typeofreport'>Ingame Ban: {{b.ingame}}</span>
                                <span class='typeofreport'>Website Ban: {{b.website}}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <button title="Double Click To Submit" v-tippy="{ animateFill: false, theme : 'gradient' }"
                        @dblclick="submit" class="newsubmitBtn" :disabled="submitting == 1">Submit <img
                            src="/img/loadwfat.svg" alt="Loading..." style="width: 10px;"
                            :style="{display: submitting ? 'inline-block' : 'none'}"></button>
            </div>
        </div>
    </div>
    <button class="newEditBtn "><i style="font-size: 14px;" class="fas fa-question"></i></button>
    <div class="modal" id="confirmCase">
        <button id="close">×</button>
        <div class="content" style="max-width: 900px;padding:0;min-height: 600px;transition: 300ms;">
            <div id="confirmBody" style='margin: 10px;padding-top: 10px;'></div>
            <button style='width:100%;margin: 0;transition: 0;' id="submitRealButton" onclick="submit()">Send</button>
        </div>
    </div>
    <div class="modal" id="addPunishment">
        <button id="close">×</button>
        <div class="content" style="max-width: 400px;padding:0;">
            <div class="field">
                <div class="fieldTitle">Select Player To Punish</div>
                <select v-model="reports.punishment.player" class="fieldSelector" id="selectPlayerToPunish">
                    <option disabled selected value="0">Choose A Player</option>
                    <option v-for="(val, key) in playersInvolved" v-if="val.type == 1 && val.name != ''">{{val.name}}
                    </option>
                </select>
            </div>
            <div class="field">
                <div class="fieldTitle">Amount Of Points Issued</div>
                <input v-model="reports.punishment.points" type="number" id="amountOfPoints" class="fieldInput"
                       placeholder="10">
            </div>
            <div class="field">
                <div class="fieldTitle">Rules Broken</div>
                <input v-model="reports.punishment.rules" type="text" id="rulesBroken" class="fieldInput"
                       placeholder="rdm, failrp, etc">
            </div>
            <div class="field">
                <div class="fieldTitle">Comments/Evidence</div>
                <textarea v-model="reports.punishment.comments" class="fieldTextarea" id="punishmentComments"
                          placeholder="Link to player report, video of offence"></textarea>
            </div>
            <button style='width:100%;margin: 0;transition: 0;border-bottom-right-radius: 3px;border-bottom-left-radius: 3px;'
                    id="submitRealButton" @click="addPunishment">Add Punishment Report
            </button>
        </div>
    </div>
    <div class="modal" id="addBan">
        <button id="close">×</button>
        <div class="content" style="max-width: 400px;padding:0;">
            <div class="field">
                <div class="fieldTitle">Select Player To Ban</div>
                <select v-model="reports.ban.player" class="fieldSelector" id="selectPlayerToPunish">
                    <option disabled selected :value="meta.choosePlayer">Choose A Player</option>
                    <option v-for="(val, key) in playersInvolved" :value="playersInvolved[key]" v-if="val.type == 1 && val.name != ''">{{val.name}}
                    </option>
                </select>
            </div>
            <div class="field">
                <div class="fieldTitle">Ban Length</div>
                <input v-model="reports.ban.length" class="fieldInput" id="bl" type="text"
                       placeholder="Ban Length (Days) (0 for perm)*"></div>
            <div class="field">
                <div class="fieldTitle">Ban Message</div>
                <input v-model="reports.ban.message" class="fieldInput" id="bm" type="text" placeholder="Ban Message*">
            </div>
            <div class="field">
                <div class="fieldTitle">Teamspeak Ban?</div>
                <select v-model="reports.ban.teamspeak" class="fieldSelector" id="ts">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select></div>
            <div class="field">
                <div class="fieldTitle">Ingame Ban?</div>
                <select v-model="reports.ban.ingame" class="fieldSelector" id="ig">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select></div>
            <div class="field">
                <div class="fieldTitle">Website Ban?</div>
                <select v-model="reports.ban.website" class="fieldSelector" id="wb">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select></div>
            <div class="field">
                <div class="fieldTitle">Permanent Ban?</div>
                <select v-model="reports.ban.permanent" class="fieldSelector" id="pb">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select></div>
            <div class="field" v-if="reports.ban.player.uuid != ''">
                <div class="fieldTitle">Automatically Add To Battlemetrics? [BETA]</div>
                <select v-model="reports.ban.battlemetrics" class="fieldSelector" id="pb">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select></div>
            <button style='width:100%;margin: 0;transition: 0;border-bottom-right-radius: 3px;border-bottom-left-radius: 3px;'
                    id="submitRealButton" @click="addBan">Add Ban Report
            </button>
        </div>
    </div>
</div>
<script>
    let vm = new Vue({
        el: '#root',
        data: {
            submitting: false,
            menus: {
                reportType: {
                    current: 'Other',
                    isOpen: false
                }
            },
            assistantStaff: [],
            playersInvolved: [
                {'name': '', 'uuid': '', 'type': 0}
            ],
            playerTypes: {
                0: 'Reporter',
                1: 'Reported'
            },
            staff_list: [],
            info: {
                description: ''
            },
            reports: {
                punishment: {
                    player: 0,
                    points: null,
                    rules: "",
                    comments: ""
                },
                ban: {
                    player: {uuid: ''},
                    length: null,
                    message: "",
                    teamspeak: 0,
                    ingame: 0,
                    website: 0,
                    permanent: 0,
                    battlemetrics: 0
                },
                added: {
                    punishments: [],
                    bans: []
                }
            },
            meta: {
                choosePlayer: {uuid: ''}
            }
        },
        methods: {
            toggleList(bind, list) {
                this.menus[bind].isOpen = !this.menus[bind].isOpen;
                $(`#${list}`).slideToggle(200);
            },
            selectReportType(type) {
                this.toggleList('reportType', 'TypeOfReportList');
                this.menus.reportType.current = type;
            },
            addAssistantStaffMember() {
                this.assistantStaff = [...this.assistantStaff, {selected: 0}];
            },
            removeAssistantStaff(key) {
                this.assistantStaff.splice(key, 1);
            },
            addPlayer() {
                this.playersInvolved = [...this.playersInvolved, {'name': '', 'uuid': '', 'type': 0}];
            },
            removePlayer(key) {
                console.log(this.playersInvolved.length);
                if (this.playersInvolved.length > 1) {
                    this.playersInvolved.splice(key, 1);
                }
            },
            submit() {
                if (this.canSubmit()) {
                    this.submitting = true;

                    let other_staff = '' + this.assistantStaff.map(e => e.selected);

                    let players = this.playersInvolved.map(e => {
                        let ne = JSON.parse(JSON.stringify(e));
                        ne.type = this.playerTypes[ne.type];
                        ne.guid = ne.uuid;
                        return ne;
                    });

                    let punishmentIDS = '[' + this.reports.added.punishments.map(e => {
                        return e.id;
                    }) + ']';
                    let banIDS = '[' + this.reports.added.bans.map(e => {
                        return e.id;
                    }) + ']';

                    $.post('/api/submitCase', {
                        lead_staff: '<?= $user->info->username; ?>',
                        other_staff: other_staff.replace(',', ' '),
                        description_of_events: this.info.description,
                        players: JSON.stringify(players),
                        type_of_report: this.menus.reportType.current,
                        punishment_reports: punishmentIDS,
                        ban_reports: banIDS
                    }, data => {
                        this.submitting = false;
                        this.reports = {
                            punishment: {
                                player: 0,
                                points: null,
                                rules: "",
                                comments: ""
                            },
                            ban: {
                                player: 0,
                                length: null,
                                message: "",
                                teamspeak: 0,
                                ingame: 0,
                                website: 0,
                                permanent: 0,
                                battlemetrics: 0
                            },
                            added: {
                                punishments: [],
                                bans: []
                            }
                        };
                        this.playersInvolved = [
                            {'name': '', 'uuid': '', 'type': 0}
                        ];
                        this.assistantStaff = [];
                        this.menus.reportType.current = 'Other';
                        this.info.description = '';
                        console.log(data);
                    })
                } else new Noty({
                    type: 'warning',
                    text: 'Invalid Case [Missing Information]',
                    timeout: 4000
                }).show();
            },
            canSubmit() {
                if (this.playersInvolved.length > 0) {
                    for (let player of this.playersInvolved) {
                        if (player.name === '') return false;
                        if (player.type > 1) return false;
                    }
                } else return false;

                if (this.countDOE < 2) return false;

                if (this.menus.reportType.current === "") return false;

                if (this.assistantStaff > 0) {
                    for (let staff of this.assistantStaff) {
                        if (staff.selected === "0") return false;
                    }

                }

                return true;
            },
            addPunishment() {
                $.post('/api/punishment', {
                    ...this.reports.punishment
                }, data => {
                    data = JSON.parse(data);

                    if (data.code === 200) {
                        this.reports.added.punishments = [...this.reports.added.punishments, {
                            id: data.response[0],
                            ...this.reports.punishment
                        }];
                        this.reports.punishment = {
                            player: 0,
                            points: null,
                            rules: "",
                            comments: ""
                        };
                        new Noty({
                            type: 'success',
                            text: `Added Punishment Report For ${this.reports.punishment.player}`,
                            timeout: 3000
                        }).show();
                    } else {
                        new Noty({
                            type: 'error',
                            text: `Failed To Add Punishment Report For ${this.reports.punishment.player} <b>[Error: ${data.message}]</b>`,
                            timeout: 3000
                        }).show();
                    }
                })
            },
            addBan() {
                $.post('/api/ban', {
                    ...this.reports.ban
                }, data => {
                    data = JSON.parse(data);

                    if (data.code === 200) {
                        this.reports.added.bans = [...this.reports.added.bans, {
                            id: data.response[0],
                            ...this.reports.ban
                        }];
                        this.reports.ban = {
                            player: 0,
                            length: null,
                            message: "",
                            teamspeak: 0,
                            ingame: 0,
                            website: 0,
                            permanent: 0,
                            battlemetrics: 0
                        };
                        new Noty({
                            type: 'success',
                            text: `Added Ban Report For ${$('#selectPlayerToBan').val()}`,
                            timeout: 3000
                        }).show();
                    } else {
                        new Noty({
                            type: 'error',
                            text: `Failed To Add Ban Report For ${$('#selectPlayerToBan').val()} <b>[Error: ${data.message}]</b>`,
                            timeout: 3000
                        }).show();
                    }
                })
            },
            updateStaffList(data) {
                this.staff_list = JSON.parse(data);

                $('#addOtherStaff').removeAttr('disabled');
            },
            getBanLength(length) {
                return (length === "0") ? length + ' Days' : 'Permanent';
            }
        },
        mounted() {
            $.get('api/getStaffList', this.updateStaffList);
        },
        computed: {
            /**
             * @return {number}
             */
            CountDOE() {
                return (this.info.description.length !== 0) ? this.info.description.trim().split(/\s+/).length : 0;
            }
        }
    });
</script>
<style>
    #torangle.open {
        transform: rotate(180deg);
    }
</style>
</body>
<!--Created By Kieran Holroyd-->
</html>