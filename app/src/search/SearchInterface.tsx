import React, {
	ChangeEvent,
	ChangeEventHandler,
	useEffect,
	useReducer,
	useState,
} from "react";
import "./style.css";

import type { APIClient, Player } from "../shared/lib";

type SearchInterfaceProps = {
	initial: {
		query: string;
		type: string;
	};
	api: APIClient;
};
type Report = {
	id: string;
	lead_staff: string;
	lead_staff_id: string;
	other_staff: string;
	typeofreport: string;
	players: Player[];
	punishments: Player[];
	bans: Player[];
	doe: string;
	timestamp: Date;
};

type SearchResponse = {
	message: string;
	code: number;
	response: SearchResult[];
};
type SearchResult = {
	id: string;
	case_id: string;
	type: string;
	description: string;

	players: {
		punished: Player[];
		reporting: Player[];
	};
	metadata: Partial<{
		[K in typeof meta_keys_const[number]]: string;
	}>;
};
const result_types = ["cases", "punishments", "bans", "unbans", "players"];
const meta_keys_const = ["name", "guid", "ban_length", "points"] as const;
const meta_keys = ["name", "guid", "ban_length", "points"];

export const SearchInterface = (props: SearchInterfaceProps) => {
	const [search, setSearch] = useState({
		types: result_types,
		type: props.initial.type,
		query: props.initial.query,
	});
	const [should_query_run, exec_query] = useReducer((s) => s + 1, 0);
	const [results, setResults] = useState<SearchResponse>({
		message: "",
		code: 0,
		response: [],
	});
	const [details, setDetailedView] = useState<Report | null>(null);

	useEffect(() => {
		const { query, type } = search;
		props.api
			.get(
				`/v2/cases/search?query=${query}&type=${
					type && type !== "" ? type : "cases"
				}`,
			)
			.then(({ data }) => {
				setResults(data);
			})
			.catch((error) => {
				console.log(error);
			});
	}, [search.type, should_query_run]);

	function loadCaseFor(case_id: string, type: string) {
		props.api.get(`/v2/cases/${case_id}/info`).then(({ data }) => {
			setDetailedView(data.response.report);
		});
	}

	/** updateURLProperties(params)
	 *
	 * This function updates URL parameters based on the given object
	 * for example `updateURLProperties({ query: "test" })`.
	 * This will update the URL to have ?query=test
	 *
	 * If the URL already has a query parameter, it will be appended to
	 * foe example `updateURLProperties({ query: "test" })`
	 *
	 * If the URL is `/?type=old` the URL will be updated
	 * to `/?type=old&query=test`
	 */
	function updateURLProperties(params: { [key: string]: string }) {
		if (!window?.history?.pushState) return;

		const url = new URL(window.location.href);
		for (const key in params) {
			url.searchParams.set(key, params[key]);
		}
		window.history.pushState({}, "", url.toString());
	}

	return (
		<div>
			<div className="searchBox-container">
				<form
					onSubmit={(e) => {
						e.preventDefault();
						exec_query();
					}}
				>
					<input
						type="text"
						className="searchBox"
						id="searchQuery"
						placeholder="Search All Cases"
						onInput={({
							target: { value: query },
						}: ChangeEvent<HTMLInputElement>) => {
							if (query) {
								setSearch({
									...search,
									query,
								});
								updateURLProperties({
									query,
								});
							}
						}}
						autoFocus
					/>
					<button className="searchCases" type="submit">
						Search
					</button>
				</form>
			</div>
			<div className="grid grid-cols-5">
				<div className="col-span-2 pl-5">
					<h1 className="relative px-5 pt-4 pb-6 text-2xl font-bold">
						Search
						<SearchTypeSelector
							type={search.type}
							types={search.types}
							onChange={({ target: { value: type } }) => {
								if (type) {
									setSearch({
										...search,
										type,
									});
									updateURLProperties({
										type,
									});
								}
							}}
						/>
						<SearchSubtext results={results} />
					</h1>
					<br />
					<div className="h-full px-2 py-3">
						{results ? (
							results.response.length > 0 ? (
								results.response.map((result) => (
									<div
										key={result.id}
										className="inline-block my-1 p-2 rounded-lg duration-200 w-full cursor-pointer hover:bg-black bg-opacity-40"
										onClick={() => {
											loadCaseFor(result.case_id, result.type);
										}}
										onKeyDown={(e) => {
											if (e.key === "Enter") {
												loadCaseFor(result.case_id, result.type);
											}
										}}
									>
										<ResultTitle type={result.type} result={result} />
										<br />
										<span>{result.description}</span>
									</div>
								))
							) : (
								<h2 className="ml-4">
									<span>No Results Found</span>
								</h2>
							)
						) : (
							<img src="/img/loadw.svg" alt="Loading" />
						)}
					</div>
				</div>
				<div className="col-span-3">
					<div className="h-full infoPanelContainer">
						<div id="case_info" className="infoPanel">
							{details ? (
								<div>
									<h1 className="relative text-2xl font-bold">
										Case
										<span className="ml-1 text-sm font-normal">
											#{details.id}
										</span>
									</h1>
									<div className="grid p-[1rem!important] grid-cols-3">
										<div className="col-span-2">
											<h2 className="text-lg font-bold">Description</h2>
											<p>{details.doe}</p>
										</div>
										<div className="col-span-1">
											{/* <h2 className="text-lg font-bold">Metadata</h2>
                      <ul className="ml-4">
                        {details.metadata ? (
                          meta_keys.map((key) => (
                            <li key={key}>
                              <span className="font-bold">{key}</span>:{" "}
                              {
                                details.metadata[
                                  key as typeof meta_keys_const[number]
                                ]
                              }
                            </li>
                          ))
                        ) : (
                          <li>None</li>
                        )}
                      </ul> */}
											<h2 className="text-lg font-bold">Players</h2>
											<ul className="ml-4">
												{details.players && details.players.length > 0 ? (
													details.players.map((player) => (
														<li key={player.guid}>
															<span
																className="font-bold"
																dangerouslySetInnerHTML={{
																	__html: player.name,
																}}
															/>{" "}
															({player.guid})
														</li>
													))
												) : (
													<li>None</li>
												)}
											</ul>
											<h2 className="text-lg font-bold">Punishments</h2>
											<ul className="ml-4">
												{details.punishments &&
												details.punishments.length > 0 ? (
													details.punishments.map((player) => (
														<li key={player.guid}>
															<span
																className="font-bold"
																dangerouslySetInnerHTML={{
																	__html: player.name,
																}}
															/>{" "}
															({player.guid})
														</li>
													))
												) : (
													<li>None</li>
												)}
											</ul>
											<h2 className="text-lg font-bold">Bans</h2>
											<ul className="ml-4">
												{details.bans && details.bans.length > 0 ? (
													details.bans.map((player) => (
														<li key={player.guid}>
															<span
																className="font-bold"
																dangerouslySetInnerHTML={{
																	__html: player.name,
																}}
															/>{" "}
															({player.guid})
														</li>
													))
												) : (
													<li>None</li>
												)}
											</ul>
										</div>
									</div>
								</div>
							) : (
								<div className="pre_title">Select Result For Details</div>
							)}
						</div>
					</div>
				</div>
			</div>
			<style>
				{`.chooseSearch {
          font-size: 32px;
          font-weight: bold;
          border: 2px solid transparent;
          border-radius: 4px;
          transition: 200ms;
          background-color: #1c1b30;
        }

        .chooseSearch:hover {
          border: 2px solid #999;
        }`}
			</style>
		</div>
	);
};
type SearchTypeSelectorProps = {
	type: string;
	types: Array<string>;

	onChange: (e: ChangeEvent<HTMLSelectElement>) => void;
};
const SearchTypeSelector = (props: SearchTypeSelectorProps) => {
	return (
		<select
			onChange={props.onChange}
			className="bg-transparent relative text-2xl font-bold ml-1 pl-1 pr-2 rounded duration-200 border-transparent border-2 hover:border-gray-400"
		>
			{props.types.map((type) => {
				return (
					<option
						key={type}
						value={type}
						selected={type === props.type}
						disabled={type === props.type}
						className="bg-[#1c1b30]"
					>
						{type.charAt(0).toUpperCase() + type.slice(1)}
					</option>
				);
			})}
		</select>
	);
};

const ResultTitle = (props: {
	type: typeof result_types[number];
	result: SearchResult;
}) => {
	const [title, _] = useState(() => {
		console.log(props.type);
		switch (props.type) {
			case "case":
				return `#${props.result.id} - ${props.result.players.reporting[0].name}`;
			case "punishment":
				return `${props.result.metadata.points} Points issued In Case #${props.result.case_id}`;
			case "ban":
				return `${props.result.metadata?.ban_length} Ban Report Was Submitted In Case #${props.result.case_id} - ${props.result.players?.punished?.[0]?.name}`;
			case "unban":
				return `Unban Report From #${props.result.id} For ${props.result.players.reporting[0].name}`;
			case "player":
				return `Player ${props.result.metadata.name}`;
		}
	});

	return <>{title}</>;
};

const SearchSubtext = ({ results }: { results: SearchResponse }) => {
	return (
		<div className="text-xs font-normal absolute bottom-0 right-0">
			{results && results.message !== ""
				? results.message
				: "Loading Search Results Found"}
		</div>
	);
};
