import React from "react";
import axios from "axios";
import { createRoot } from "react-dom/client";
import { Viewer } from "./Viewer";
import PusherJS from "pusher-js";
import "./style.css";

document.addEventListener("DOMContentLoaded", () => {
  // const query = (
  //   document.getElementsByName(
  //     "data-search-query"
  //   ) as NodeListOf<HTMLMetaElement>
  // )[0].content;
  const pusherID = (
    document.getElementsByName("data-pusher-id") as NodeListOf<HTMLMetaElement>
  )[0].content;
  const pusherCluster = (
    document.getElementsByName(
      "data-pusher-cluster"
    ) as NodeListOf<HTMLMetaElement>
  )[0].content;

  let pusher = new PusherJS(pusherID, {
    cluster: pusherCluster,
    forceTLS: true,
    enabledTransports: ["ws"],
  });

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
    return <Viewer api={apiClient} pusher={pusher} />;
  };

  const app_root = createRoot(document.querySelector("#app")!);
  app_root.render(<App />);
});
