import React from "react";
import { createRoot } from "react-dom";
import { Meeting } from "./Meeting";
import PusherJS from "pusher-js/dist/web/pusher";

document.addEventListener("DOMContentLoaded", () => {
  const meetingDate =
    document.getElementsByName("data-meeting-date")[0].content;
  const meetingID = document.getElementsByName("data-meeting-id")[0].content;
  const pusherID = document.getElementsByName("data-pusher-id")[0].content;
  let pusher = new PusherJS(pusherID, {
    cluster: "eu",
    forceTLS: true,
    enabledTransports: ["ws"],
  });

  const App = (props) => {
    return (
      <Meeting date={props.meetingDate} id={props.meetingID} pusher={pusher} />
    );
  };

  const app_root = createRoot(document.querySelector("#app"));
  app_root.render(
    <App meetingDate={meetingDate} meetingID={meetingID} pusherID={pusherID} />
  );
});
