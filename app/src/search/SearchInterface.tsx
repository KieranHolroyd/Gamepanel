import React, { useEffect, useReducer, useState } from "react";
import type { AxiosInstance } from "axios";
import "../style.css";

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
  const [should_query_run, exec_query] = useReducer((s) => s + 1, 0);
  const [results, setResults] = useState<SearchResult[]>([]);

  useEffect(() => {
    const { query, type } = search;
    props.api
      .get(
        `/v2/cases/search?query=${query}&type=${
          type && type !== "" ? type : "cases"
        }`
      )
      .then(({ data }) => {
        setResults(data.response);
      })
      .catch((error) => {
        console.log(error);
      });
  }, [search.type, should_query_run]);

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
          <div id="reports" className="h-full selectionPanel">
            {results ? (
              results.length > 0 ? (
                results.map((result) => (
                  <div className="selectionTab" onClick={() => {}}>
                    <ResultTitle type={result.type} result={result} />
                    <br />
                    <span>{result.description}</span>
                  </div>
                ))
              ) : (
                <h2 style={{ marginLeft: "1em" }}>
                  <span>No Results Found</span>
                </h2>
              )
            ) : (
              <img src="/img/loadw.svg" />
            )}
          </div>
        </div>
        <div className="grid__col grid__col--4-of-6">
          <div className="h-full infoPanelContainer">
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
