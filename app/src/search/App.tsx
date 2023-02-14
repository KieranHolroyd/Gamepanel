import React from "react";
import axios from "axios";
import { createRoot } from "react-dom/client";
import { SearchInterface } from "./SearchInterface";
import "../style.css";

document.addEventListener("DOMContentLoaded", () => {
  const query = (
    document.getElementsByName(
      "data-search-query"
    ) as NodeListOf<HTMLMetaElement>
  )[0].content;
  const type = (
    document.getElementsByName(
      "data-search-type"
    ) as NodeListOf<HTMLMetaElement>
  )[0].content;

  const apiClient = axios.create({
    baseURL: "/api",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Accept: "application/json",
      // "X-CSRFToken": document.getElementsByName("csrfmiddlewaretoken")[0].content TODO: Add CSRF token
    },
    withCredentials: true,
  });

  const App = () => {
    return <SearchInterface initial={{ query, type }} api={apiClient} />;
  };

  const app_root = createRoot(document.querySelector("#app")!);
  app_root.render(<App />);
});
