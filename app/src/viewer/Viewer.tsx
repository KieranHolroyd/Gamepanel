import React, { useEffect, useMemo, useReducer, useRef, useState } from "react";
import "./style.css";
import type { APIClient, Player, PusherClient } from "../shared/lib";

type ViewerProps = {
	api: APIClient;
	pusher: PusherClient;
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
					<input type="text" className="searchBox" id="searchQuery" placeholder="Search All Cases" />
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
							onKeyDown={(e) => {
								if (e.key === "Enter") {
									cases.reload();
								}
							}}
							className="fas fa-redo-alt float-right cursor-pointer"
						/>
					</h1>
					<div className="selectionPanel">
						{cases.cases.map((c) => (
							<div
								key={c.id}
								className="selectionTab"
								onClick={() => {
									get_case_with_id(c.id);
								}}
								onKeyDown={(e) => {
									if (e.key === "Enter") {
										get_case_with_id(c.id);
									}
								}}
							>
								<span className="float-right text-xs">Lead: {c.lead_staff}</span>
								<span className="text-2xl">
									{c.id}-{c.reporting_player[0].name}
								</span>
								<br />
								<span className="text-xs">
									{c.pa && <span className="punishmentincase">Punishment Report</span>}
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
									<h2 className="text-2xl font-semibold mx-2 mt-2 mb-4">
										Case ID: {current.id}-{current.players[0].name}
									</h2>
									<div className="mb-4">
										<div className="mx-2">
											<span className="text-gray-300 font-semibold">Lead Staff:</span>{" "}
											<a href={`/staff/#User:${current.lead_staff[0].id}`}>{current.lead_staff[0].username}</a>
										</div>
										{current.other_staff.length > 0 && (
											<div className="mx-2">
												<span className="text-gray-300 font-semibold">Assistant Staff:</span>{" "}
												{current.other_staff.map((s) => (
													<a key={s.username} href={`/staff/#User:${s.id}`}>
														{s.username}
													</a>
												))}
											</div>
										)}
									</div>
									<div className="mx-2">
										<span className="text-gray-300 font-semibold">Description Of Events:</span>
										<div className="max-w-md ml-4">{current.doe}</div>
									</div>
									<div className="mx-2">
										<span className="text-gray-300 font-semibold">Type Of Report:</span> {current.typeofreport}
									</div>
									<div className="mx-2">
										<span className="text-gray-300 font-semibold">Report Timestamp:</span> {current.timestamp}
									</div>
									{current.punishments?.map((p) => (
										<div dangerouslySetInnerHTML={{ __html: p.html }} />
									))}
									{current.bans?.map((b) => (
										<div dangerouslySetInnerHTML={{ __html: b.html }} />
									))}
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
