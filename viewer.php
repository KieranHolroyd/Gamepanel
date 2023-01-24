<?php include "head.php";
Guard::init()->SLTRequired();
?>
<div class="searchBox-container">
    <a href="./search?type=cases"><input type="text" class="searchBox" id="searchQuery" placeholder="Search All Cases"><button class="searchCases" id="searchCases">Search</button></a>
</div>
<div class="grid new" id="root" v-cloak>
    <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new">Case List <i @click="loadCases" class="fas fa-redo-alt"
                                                style="float: right;cursor: pointer;"></i></h1>
        <div style='height: calc(100vh - 118px) !important;overflow: auto;' class="selectionPanel">
            <div class="selectionTab" v-for="r in reports" @click="loadFullCase(r.id)">
                <span style="float: right;font-size: 12px;">Lead: {{r.lead_staff}}</span>
                <span style="font-size: 25px;">{{r.id}}-{{r.reporting_player[0].name}}<br>
                    <span style="font-size: 12px; padding: 0;">
                        <span v-if="r.pa" class="punishmentincase">Punishment Report</span>
                        <span v-if="r.ba" class="banincase">Ban Report</span>
                        <span class="timestamp">{{r.timestamp}}</span>
                        <span class="typeofreport">{{r.typeofreport}}</span>
                    </span>
                </span>
            </div>
        </div>
    </div>
    <div class="grid__col grid__col--4-of-6">
        <div class="infoPanelContainer" style='height: calc(100vh - 49px);overflow: auto;'>
            <div v-if="!fullReportOpen" class="infoPanel">
                <div class="pre_title">Select Case For Details</div>
            </div>
            <div v-else id="case_info" class="infoPanel">
                <h2><span>Case ID:</span> {{openReport.id}}-<span v-html="openReport.players[0].name"></span></h2>
                <p id="case"><span>Lead Staff:</span> <span v-html="openReport.lead_staff"></span></p>
                <p id="case"><span>Other Staff:</span> <span v-html="openReport.other_staff"></span></p>
                <p id="case"><span>Type Of Report:</span><br> {{openReport.typeofreport}}</p>
                <section style="padding-left: 10px;" style="text-transform: capitalize;">
                    <span>Players Involved:</span>
                    <p v-for="p in openReport.players" style="color: #999;margin: 4px 0;">{{p.type}}: <span v-html="p.name"></span></p>
                </section>
                <p id="case"><span>Description Of Events:</span><br> {{openReport.doe}}</p>
                <p id="case"><span>Timestamp:</span> {{openReport.timestamp}}</p>
                <div v-for="pr in openReport.punishments" v-html="pr.html"></div>
                <div v-for="br in openReport.bans" v-html="br.html"></div>
            </div>
        </div>
    </div>
</div>
<script>
    let vm = new Vue({
        el: '#root',
        data: {
            reports: [],
            openReport: {},
            fullReportOpen: false,
            offset: 0
        },
        methods: {
            loadFullCase(id) {
                $.post('api/v1/getMoreInfo', {'id': id}, data => {
                    data = JSON.parse(data);

                    if (data.code === 200) {
                        this.openReport = data.response.report;
                        this.fullReportOpen = true;
                    }
                });
            },
            loadCases() {
                $.post('api/v2/cases/getfull', {'offset': this.offset}, data => {
                    data = JSON.parse(data);
                    this.reports = [];
                    for (let i = 0; i < Object.keys(data.caseno).length; i++) {
                        let c = data.caseno[i];
                        this.reports.push(c);
                    }
                });
            },
            loadLiveCase(data) {
                this.reports.unshift(data);
            }
        },
        mounted() {
            this.loadCases();
        }
    });

    let caseInfoChannel = pusher.subscribe(`caseInformation`);
    caseInfoChannel.bind("receive", vm.loadLiveCase);
</script>
