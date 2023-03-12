import React, { useEffect, useRef, useState } from "react";
import { LineChart, Line, CartesianGrid, XAxis, YAxis, Tooltip } from "recharts";
import Clock from "../shared/component/clock";
import { APIClient } from "../shared/lib";
import "./style.css";

type UserFrontEndInfo = {
	isSLT: string;
	isStaff: string;
	isDeveloper: string;
	isSuspended: string;
	isPD: string;
	isEMS: string;
	isOnLOA: string;
	id: string;
	rank: string;
	firstName: string;
	lastName: string;
	displayName: string;
	username: string;
	team: string;
	faction_rank: string;
	faction_rank_real: string;
};
type DashboardProps = {
	user: {
		info: {
			needed: boolean;
			fields_required: Array<string>;
		};
	} & UserFrontEndInfo;
	config: {
		panel: {
			enabled: boolean;
		};
		org: {
			name: string;
		};
	};
	api: APIClient;
};

type ServerStatistics = {
	total: {
		players: number;
		police: number;
		medics: number;
		balance: string;
	};
	richlist: Array<{
		uid: number;
		playerid: string;
		name: string;
		bankacc: string;
		last_seen: string;
	}>;
};

type CaseStatObject = {
	name: string;
	value: number;
};
type CasesStatistics = {
	daily: CaseStatObject[];
	weekly: CaseStatObject[];
	monthly: CaseStatObject[];
};

export function Dashboard(props: DashboardProps) {
	const [stats, setStats] = useState<ServerStatistics>({
		total: {
			players: 0,
			police: 0,
			medics: 0,
			balance: "$000,000,000",
		},
		richlist: [],
	});
	const [cases, setCases] = useState<CasesStatistics>({
		daily: [],
		weekly: [],
		monthly: [],
	});

	useEffect(() => {
		props.api.get("/v2/statistics/game/server").then(({ data }) => {
			if (data.code === 200) {
				setStats({
					...stats,
					total: {
						players: data.response.players.total,
						police: data.response.players.total_cops,
						medics: data.response.players.total_medics,
						balance: data.response.serverBalance.formatted,
					},
					richlist: data.response.players.rich_list,
				});
			}
		});
		props.api
			.get<{
				success: boolean;
				stats: {
					daily: number[];
					weekly: number[];
					monthly: number[];
				};
			}>("/v2/statistics/cases")
			.then(({ data }) => {
				console.log(data.stats);
				if (data.success) {
					setCases({
						daily: data.stats.daily
							.map((x, ix) => {
								return { name: `${ix} days ago`, value: x };
							})
							.reverse(),
						weekly: data.stats.weekly
							.map((x, ix) => {
								return { name: `${ix} weeks ago`, value: x };
							})
							.reverse(),
						monthly: data.stats.monthly
							.map((x, ix) => {
								return { name: `${ix} months ago`, value: x };
							})
							.reverse(),
					});
				}
			});
	}, []);

	return (
		<div>
			<div className="dashboardOverlay">
				<div className="p-2 z-10">
					<h1 className="inline-block text-3xl">
						{props.user.username} <small className="font-light">{props.user.rank}</small>
					</h1>
					<h1 className="float-right inline-block">
						<Clock />
					</h1>
				</div>
				<h4 className="fixed bottom-2.5 right-2.5">{props.config.org.name} Dashboard</h4>
				{props.config.panel.enabled ? (
					<div className="mr-4">
						<div className="grid grid-cols-3 gap-2.5 my-2 ml-0">
							<div className="stat col">
								<p>Total Players</p>
								<span id="totalplayers">{stats.total.players.toString().padStart(3, "0")}</span>
							</div>
							<div className="stat col">
								<p>Total Police</p>
								<span id="totalcops">{stats.total.police.toString().padStart(3, "0")}</span>
							</div>
							<div className="stat col">
								<p>Total Medics</p>
								<span id="totalmedics">{stats.total.medics.toString().padStart(3, "0")}</span>
							</div>
						</div>
						<div className="stat">
							<p>Server Balance</p>
							<span id="serverbalance">{stats.total.balance.toLocaleString()}</span>
						</div>
						<div className="stat">
							<p>Rich List</p>
							<div id="rich_list">
								{stats.richlist.map((player, idx) => (
									<div className="richListPlayer">
										No. {idx + 1}: <a href={`/game/players?query=${player.name}`}>{player.name}</a> ~ ${player.bankacc}
									</div>
								))}
							</div>
						</div>
					</div>
				) : undefined}

				<div id="staff_info" className="case_stats infoPanel">
					<div className="cool-graph daily-cases">
						<b>Daily Cases</b>
						<LineChart width={600} height={300} data={cases.daily} margin={{ top: 5, right: 20, bottom: 5, left: 0 }}>
							<Line type="monotone" dataKey="value" stroke="#8884d8" />
							<XAxis dataKey="name" />
							<YAxis />
							<Tooltip labelStyle={{ color: "black" }} />
						</LineChart>
					</div>
					<div className="cool-graph weekly-cases">
						<b>Weekly Cases</b>
						<LineChart width={600} height={300} data={cases.weekly} margin={{ top: 5, right: 20, bottom: 5, left: 0 }}>
							<Line type="monotone" dataKey="value" stroke="#8884d8" />
							<XAxis dataKey="name" />
							<YAxis />
							<Tooltip labelStyle={{ color: "black" }} />
						</LineChart>
					</div>
					<div className="cool-graph weekly-cases">
						<b>Monthly Cases</b>
						<LineChart width={600} height={300} data={cases.monthly} margin={{ top: 5, right: 20, bottom: 5, left: 0 }}>
							<Line type="monotone" dataKey="value" stroke="#8884d8" />
							{/* <CartesianGrid stroke="#ccc" strokeDasharray="5 5" /> */}
							<XAxis dataKey="name" />
							<YAxis />
							<Tooltip labelStyle={{ color: "black" }} />
						</LineChart>
					</div>
				</div>
			</div>
			{/* <script>
  //     function getGraphs() {
  //         apiclient.get("api/v2/statistics/cases/daily").then(({
  //             data: cases
  //         }) => {
  //             new Chartist.Line('.daily-cases', {
  //                 labels: ['Four Days Ago', 'Three Days Ago', 'Two Days Ago', 'Yesterday', 'Today'],
  //                 series: [
  //                     [cases.fourdays, cases.threedays, cases.twodays, cases.yesterday, cases.today]
  //                 ]
  //             }, {
  //                 chartPadding: {
  //                     right: 10,
  //                     top: 20
  //                 }
  //             });
  //         }).catch(noty_catch_error)
  //         apiclient.get("api/v2/statistics/cases/weekly").then(({
  //             data: cases
  //         }) => {
  //             new Chartist.Line('.weekly-cases', {
  //                 labels: ['A Month Ago', 'Three Weeks Ago', 'Two Weeks Ago', 'Last Week', 'This Week'],
  //                 series: [
  //                     [cases.onemonth, cases.threeweeks, cases.twoweeks, cases.lastweek, cases.thisweek]
  //                 ]
  //             }, {
  //                 chartPadding: {
  //                     right: 10,
  //                     top: 20
  //                 }
  //             });
  //             $('#staff_info').css('opacity', 1);
  //         }).catch(noty_catch_error)

  //     }

  //     function getStats() {
  //         apiclient.get('api/v2/statistics/game/server').then(({
  //             data
  //         }) => {
  //             if (data.code === 200) {
  //                 $('#totalplayers').text(data.response.players.total);
  //                 $('#totalcops').text(data.response.players.total_cops);
  //                 $('#totalmedics').text(data.response.players.total_medics);
  //                 $('#serverbalance').text(data.response.serverBalance.formatted);
  //                 $('#rich_list').html('');
  //                 for (let key in Object.keys(data.response.players.rich_list)) {
  //                     const user = data.response.players.rich_list[key];
  //                     const real_key = parseInt(key) + 1;
  //                     $('#rich_list').append(`<div className="richListPlayer">Number ${real_key}: <a href="/game/players?query=${user.name}">${user.name}</a> ~ ${user.bankacc}</div>`);
  //                 }
  //             }
  //         })
  //     }
  //     $('#dtime').text(currentTime());
  //     setInterval(() => {
  //         $('#dtime').text(currentTime());
  //     }, 1000);
  //     getStats();
  //     getGraphs();

  //     function selectBG(bg, custom) {
  //         if (!custom) {
  //             Cookies.set('cbg', '<?= Config::$base_url; ?>img/bg' + bg + '.png', {
  //                 expires: 720
  //             });
  //             $('#selectBG' + bg).text('[SELECTED]');
  //             $('body').css('background-image', 'url("img/bg' + bg + '.png")');
  //         }
  //     }

  //     function setCustomBackground() {
  //         let cimg = $('#cimg').val();
  //         Cookies.set('cbg', cimg, {
  //             expires: 720
  //         });
  //         $('body').css('background-image', 'url("' + cimg + '")');
  //     }
  //     let vm = new Vue({
  //         el: '#app',
  //         data: {
  //             user: {
  //                 info: {}
  //             },
  //             loaded: false
  //         }
  //     });
  //     apiclient.get(`api/v2/user/me_new`).then(({
  //         data
  //     }) => {
  //         if (data.success) {
  //             vm.user = data.user;
  //             vm.loaded = true;
  //         }
  //     }).catch(noty_catch_error);

  //     function userArrayLoaded() {
  //         return false;
  //     }
  //  </script>*/}
			{/* <?php if ($user->needMoreInfo()) : ?> */}
		</div>
	);
}

/* <div className="modal" id="selectBG">
//       <button id="close">×</button>
//       <div className="content" style="max-width: 900px;border-radius: 5px;">
//           <h2>Choose A Background Image</h2>
//           <p>Background 1 (Default) <span id="selectBG1" style="cursor:pointer;" onclick="selectBG(1, false)"><?php if ($_COOKIE['bg'] === "1") {
//                                                                                                                   echo "[SELECTED]";
//                                                                                                               } else {
//                                                                                                                   echo "[SELECT]";
//                                                                                                               } ?></span></p>
//           <img src="https://cdn.discordapp.com/attachments/528343271840153620/528474876739190793/wallpaper_1.jpg" onclick="selectBG(3, false)" style="border-radius: 5px;box-shadow: 0 0 5px 0 rgba(0,0,0,0.3);margin:5px;width: calc(100% - 10px);" alt="Background 1 (Default)" title="Background 1 (Default)">
//           <p>Background 2 <span id="selectBG2" style="cursor:pointer;" onclick="selectBG(2, false)"><?php if ($_COOKIE['bg'] === "2") {
//                                                                                                           echo "[SELECTED]";
//                                                                                                       } else {
//                                                                                                           echo "[SELECT]";
//                                                                                                       } ?></span></p>
//           <img src="/img/bg2.png" onclick="selectBG(2, false)" style="border-radius: 5px;box-shadow: 0 0 5px 0 rgba(0,0,0,0.3);margin:5px;width: calc(100% - 10px);" alt="Background 2" title="Background 2">
//           <p>Have Your Own Background? [E.G. Imgur/gyazo links] <?php if (isset($_COOKIE['cbg'])) {
//                                                                       echo "[SELECTED]";
//                                                                   } ?></p>
//           <div className="field"><input className="fieldInput" style="background-color: #222;margin-top: 10px;" id="cimg" type="text" onkeyup="setCustomBackground();" placeholder="Your Link..." <?php if (isset($_COOKIE['cbg'])) {
//                                                                                                                                                                                               echo "value='" . htmlspecialchars(strip_tags($_COOKIE['cbg'])) . "'";
//                                                                                                                                                                                           } ?>></div>
//           <button type="button" style="margin-top: 10px;" class="newsubmitBtn" onclick="setCustomBackground();">Set Custom
//               Image
//           </button>
//       </div>
//   </div>
<div className="modal" id="moreinfoneeded" style="display: block;">
          <button id="close">×</button>
          <div className="content open" style="max-width: 900px;border-radius: 5px;">
              <h2>Hold on a second,</h2>
              <p>We need some information about you</p><br>
              <?php
              if (in_array('region', $user->neededFields)) {
                  echo "<div className='field'>
                      <div className='fieldTitle'>Your Region</div>
                      <select className='fieldSelector' id='userRegion'>
                          <option selected disabled>Choose A Global Region</option>
                          <option value='EU'>European Union</option>
                          <option value='NA'>North America</option>
                          <option value='SA'>South America</option>
                          <option value='AF'>Africa</option>
                          <option value='AS'>Asia</option>
                          <option value='AU'>Oceania</option>
                      </select></div>";
              }
              if (in_array('steamid', $user->neededFields)) {
                  echo "<div className='field'>
                          <div className='fieldTitle'>Your Steam ID</div>
                          <input type='text' id='userSteamID' className='fieldInput' placeholder='Steam 64 ID'>
                      </div>";
              }
              if (in_array('discord_tag', $user->neededFields)) {
                  echo "<div className='field'>
                          <div className='fieldTitle'>Your Discord Tag</div>
                          <input type='text' id='userDiscordTag' className='fieldInput' placeholder='Example: Kieran#1234'>
                  </div>";
              }
              ?>
              <button onclick="saveNeededInfo()" className="createPointBtn">Save information</button>
          </div>
      </div>

/* <script>
          let needed = `<?= json_encode($user->neededFields); ?>`;

          function saveNeededInfo() {
              let needParse = JSON.parse(needed);
              if (needParse.indexOf('discord_tag') > -1) {
                  console.log(userArray.info.id);
                  $.post('/api/v1/saveStaffDiscordTag', {
                      tag: $('#userDiscordTag').val(),
                      id: userArray.info.id
                  }, data => {
                      new Noty({
                          text: 'Saved Discord Tag, Once All Tasks Complete, Reload The Page.',
                          type: 'success'
                      }).show();
                  });
              }
              if (needParse.indexOf('region') > -1) {
                  console.log(userArray.info.id);
                  $.post('/api/v1/saveStaffRegion', {
                      region: $('#userRegion').val(),
                      id: userArray.info.id
                  }, data => {
                      new Noty({
                          text: 'Saved Region, Once All Tasks Complete, Reload The Page.',
                          type: 'success'
                      }).show();
                  });
              }
              if (needParse.indexOf('steamid') > -1) {
                  console.log(userArray.info.id);
                  $.post('/api/v1/saveStaffUID', {
                      uid: $('#userSteamID').val(),
                      id: userArray.info.id
                  }, data => {
                      new Noty({
                          text: 'Saved SteamID, Once All Tasks Complete, Reload The Page.',
                          type: 'success'
                      }).show();
                  });
              }
          }
      </script> */
