import React from "react";
import { createRoot } from "react-dom/client";
import { Dashboard } from "./Dashboard";
import axios from "axios";

document.addEventListener("DOMContentLoaded", () => {
	const user_info = JSON.parse(
		(document.getElementsByName("data-user-info") as NodeListOf<HTMLMetaElement>)[0].content,
	);
	const need_more_info =
		(document.getElementsByName("data-user-isMoreNeeded") as NodeListOf<HTMLMetaElement>)[0].content === "true";
	const fields_required = (
		document.getElementsByName("data-user-fieldsRequired") as NodeListOf<HTMLMetaElement>
	)[0].content.split(",");

	const is_panel_enabled =
		(document.getElementsByName("data-enabled-panel") as NodeListOf<HTMLMetaElement>)[0].content === "true";

	const org_name = (document.getElementsByName("data-org-name") as NodeListOf<HTMLMetaElement>)[0].content;

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
			<Dashboard
				user={{ ...user_info, info: { needed: need_more_info, fields_required } }}
				config={{ panel: { enabled: is_panel_enabled }, org: { name: org_name } }}
				api={apiClient}
			/>
		);
	};

	const app_root = createRoot(document.querySelector("#app")!);
	app_root.render(<App />);
});
