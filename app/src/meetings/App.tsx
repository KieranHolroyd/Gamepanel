import React from "react";
import { createRoot } from "react-dom/client";
import { Meeting } from "./Meeting";
import PusherJS from "pusher-js";
import axios from "axios";

document.addEventListener("DOMContentLoaded", () => {
  const meetingDate = (
    document.getElementsByName(
      "data-meeting-date"
    ) as NodeListOf<HTMLMetaElement>
  )[0].content;
  const meetingID = (
    document.getElementsByName("data-meeting-id") as NodeListOf<HTMLMetaElement>
  )[0].content;
  const pusherID = (
    document.getElementsByName("data-pusher-id") as NodeListOf<HTMLMetaElement>
  )[0].content;

  let pusher = new PusherJS(pusherID, {
    cluster: "eu",
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
    return (
      <Meeting
        date={meetingDate}
        id={meetingID}
        pusher={pusher}
        api={apiClient}
      />
    );
  };

  const app_root = createRoot(document.querySelector("#app")!);
  app_root.render(<App />);
});
