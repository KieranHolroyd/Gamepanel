import React, { useState } from "react";
import type { AxiosInstance } from "axios";
type SearchInterfaceProps = {
  initial: {
    query: string;
    type: string;
  };
  api: AxiosInstance;
};
type Player = any;
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
    name: string;
    guid: string;
    ban_length: string;
    points: string;
  }>;
};
const result_types = ["cases", "punishments", "bans", "unbans", "players"];

export const SearchInterface = (props: SearchInterfaceProps) => {
  const [search, setSearch] = useState({
    types: result_types,
    type: props.initial.type,
    query: props.initial.query,
  });
  const [results, setResults] = useState<SearchResult[]>([]);

  const run_search_query = (e?: React.FormEvent<HTMLFormElement>) => {
    e?.preventDefault();
    const { query, type } = search;
    props.api
      .get(`/v2/cases/search?query=${query}&type=${type}`)
      .then(({ data }) => {
        setResults(data.response);
      })
      .catch((error) => {
        console.log(error);
      });
  };

  return (
    <div>
      <div className="searchBox-container">
        <form onSubmit={run_search_query}>
          <input
            type="text"
            className="searchBox"
            id="searchQuery"
            placeholder="Search All Cases"
            onInput={(e) =>
              setSearch({
                ...search,
                query: (e.target as HTMLInputElement).value,
              })
            }
            autoFocus
          />
          <button className="searchCases" type="submit">
            Search
          </button>
        </form>
      </div>
      <div className="grid new">
        <div
          className="grid__col grid__col--2-of-6"
          style={{ paddingLeft: "20px !important" }}
        >
          <h1 className="info-title new">
            Search{" "}
            <select
              onChange={(e) => {
                setSearch({
                  ...search,
                  type: (e.target as HTMLSelectElement).value,
                });
                run_search_query();
              }}
              id="searchTypeChooser"
              className="chooseSearch"
            >
              {search.types.map((type) => {
                return (
                  <option value={type} selected={type === search.type}>
                    {type.charAt(0).toUpperCase() + type.slice(1)}
                  </option>
                );
              })}
            </select>
            <div
              style={{ float: "right", fontSize: "14px", color: "#999" }}
              id="resultsfound"
            >
              Loading Search Results Found
            </div>
          </h1>
          <br />
          <div
            id="reports"
            style={{ height: "calc(100vh - 122px) !important;" }}
            className="selectionPanel"
          >
            {results && results.length > 0 ? (
              results.map((result) => (
                <div className="selectionTab" onClick={() => {}}>
                  <ResultTitle type={search.type} result={result} />
                  <br />
                  <span>{result.description}</span>
                </div>
              ))
            ) : (
              <img src="/img/loadw.svg" />
            )}
          </div>
        </div>
        <div className="grid__col grid__col--4-of-6">
          <div
            className="infoPanelContainer"
            style={{ height: "calc(100vh - 49px);" }}
          >
            <div id="case_info" className="infoPanel">
              <div className="pre_title">Select Result For Details</div>
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

const ResultTitle = (props: {
  type: typeof result_types[number];
  result: SearchResult;
}) => {
  const [title, _] = useState(() => {
    console.log(props.type);
    switch (props.type) {
      case "cases":
        return `#${props.result.id} - ${props.result.players.reporting[0].name}`;
      case "punishments":
        return `${props.result.metadata.points} Points issued In Case #${props.result.case_id}`;
      case "bans":
        return `${props.result.metadata?.ban_length} Ban Report Was Submitted In Case #${props.result.case_id} - ${props.result.players?.punished?.[0]?.name}`;
      case "unbans":
        return `Unban Report From #${props.result.id} For ${props.result.players.reporting[0].name}`;
      case "players":
        return `Player ${props.result.metadata.name}`;
    }
  });

  return <>{title}</>;
};
