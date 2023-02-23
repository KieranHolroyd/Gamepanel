import React, { useEffect, useMemo, useReducer, useRef, useState } from "react";
import "./style.css";
import type { AxiosInstance } from "axios";
import type PusherJS from "pusher-js";
import { Player } from "../shared/lib";

type ViewerProps = {
  api: AxiosInstance;
  pusher: PusherJS;
};
type APIDataTypes = {
  reload: () => void;
  cases: CaseLog[];
};
type CaseLog = {
  id: number;
  ba: boolean;
  pa: boolean;
  ltpr: string;
  lead_staff: string;
  reporting_player: Player[];
  timestamp: string;
  typeofreport: string;
};
type FullCaseLog = {
  id: number;
  lead_staff: Staff[];
  other_staff: Staff[];
  typeofreport: string;
  players: Player[];
  punishments: any[];
  bans: any[];
  doe: string;
  timestamp: string;
};

type Staff = {
  id: number;
  username: string;
};

export function Viewer(props: ViewerProps) {
  const [cases_reload, reloadCases] = useReducer((s) => s + 1, 0);

  const [cases, setCases] = useState<APIDataTypes>({
    reload: reloadCases,
    cases: [],
  });
  const cases_ref = useRef(cases);
  const [current, setCurrent] = useState<FullCaseLog | null>(null);

  useEffect(() => {
    const channel = props.pusher.subscribe("caseInformation");
    channel.bind("receive", (data: CaseLog) => {
      const caseref = cases_ref.current.cases;
      caseref.unshift(data);
      setCases({ ...cases_ref.current, cases: caseref });
    });
    reloadCases();

    return () => {
      channel.unbind_all();
      channel.unsubscribe();
    };
  }, []);

  useEffect(() => {
    props.api.get("v2/cases/list").then((res) => {
      const cases = [];
      for (const c of res.data.caseno) {
        cases.push(c);
      }
      setCases({ reload: reloadCases, cases });
    });
  }, [cases_reload]);

  function get_case_with_id(id: number) {
    props.api.get(`v2/cases/${id}/info`).then((res) => {
      if (res.data.code === 200) {
        setCurrent(res.data.response.report);
      }
    });
  }

  // This is the problem child
  useEffect(() => {
    cases_ref.current = cases;
  }, [cases]);

  return (
    <>
      <div className="searchBox-container">
        <a href="./search?type=cases">
          <input
            type="text"
            className="searchBox"
            id="searchQuery"
            placeholder="Search All Cases"
          />
          <button className="searchCases" id="searchCases">
            Search
          </button>
        </a>
      </div>
      <div className="grid grid-cols-6" id="root">
        <div className="col-span-2 pl-5">
          <h1 className="info-title new">
            Case List{" "}
            <i
              onClick={cases.reload}
              className="fas fa-redo-alt float-right cursor-pointer"
            ></i>
          </h1>
          <div className="selectionPanel">
            {cases.cases.map((c) => (
              <div
                key={c.id}
                className="selectionTab"
                onClick={() => {
                  get_case_with_id(c.id);
                }}
              >
                <span className="float-right text-xs">
                  Lead: {c.lead_staff}
                </span>
                <span className="text-2xl">
                  {c.id}-{c.reporting_player[0].name}
                </span>
                <br />
                <span className="text-xs">
                  {c.pa && (
                    <span className="punishmentincase">Punishment Report</span>
                  )}
                  {c.ba && <span className="banincase">Ban Report</span>}
                  <span className="timestamp">{c.timestamp}</span>
                  <span className="typeofreport">{c.typeofreport}</span>
                </span>
              </div>
            ))}
          </div>
        </div>
        <div className="col-span-4">
          <div className="infoPanelContainer sticky top-16">
            <div className="infoPanel">
              {current ? (
                <>
                  <h2 className="text-2xl">
                    Case ID: {current.id}-{current.players[0].name}
                  </h2>
                  <p id="case">
                    <span>Lead Staff:</span>{" "}
                    <a href={`/staff/#User:${current.lead_staff[0].id}`}>
                      {current.lead_staff[0].username}
                    </a>
                  </p>
                  {current.other_staff.length > 0 && (
                    <p id="case">
                      <span>Assistant Staff:</span>{" "}
                      {current.other_staff.map((s) => (
                        <a key={s.username} href={`/staff/#User:${s.id}`}>
                          {s.username}
                        </a>
                      ))}
                    </p>
                  )}
                  <p id="case">
                    <span>Type Of Report:</span> {current.typeofreport}
                  </p>
                  {current.punishments &&
                    current.punishments.map((p) => <div v-html="p.html"></div>)}
                  {current.bans &&
                    current.bans.map((b) => <div v-html="b.html"></div>)}
                </>
              ) : (
                <h1 className="pre_title">No Case Selected</h1>
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
/* <script>
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
                $.get(`api/v2/cases/${id}/info`, data => {
                    data = JSON.parse(data);

                    if (data.code === 200) {
                        this.openReport = data.response.report;
                        this.fullReportOpen = true;
                    }
                });
            },
            loadCases() {
                $.get('api/v2/cases/list', {
                    'offset': this.offset
                }, data => {
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
</script> */
