import React, { useEffect, useRef, useState } from "react";
import type { AxiosInstance } from "axios";
import type PusherJS from "pusher-js";
import { Loading } from "../shared/component/loading";

type MeetingProps = {
	date: string;
	id: string;
	pusher: PusherJS;
	api: AxiosInstance;
};

type UserFrontEndInfo = {
	id: number;

	rank: string;
	username: string;
	firstName: string;
	lastName: string;
	displayName: string;
	team: number;

	isSLT: boolean;
	isStaff: boolean;
	isDeveloper: boolean;
	isSuspended: boolean;
	isPD: boolean;
	isEMS: boolean;
	isOnLOA: boolean;

	faction_rank: number | boolean;
	faction_rank_real?: string;
};

type MPoint = {
	id: number;
	name: string;
	author: string;
	description?: string;
	meetingID?: number;

	comments: Array<Partial<MPointComment>>;

	votes_up?: number;
	votes_down?: number;
	canDelete?: boolean;
};

type MPointComment = {
	id: number;
	pointID: number;
	content: string;
	author: UserFrontEndInfo;

	created_at: string;
	updated_at: string;

	canDelete?: boolean;
};

type MNewPoint = {
	title: string;
	description: string;
};

type MNewComment = {
	content: string;
};

type MPointDelete = { deleteID: string };

export const Meeting = (props: MeetingProps) => {
	const [point, setPoint] = useState<MPoint>();
	const point_reference = useRef(point);

	const [points, setPoints] = useState<Array<MPoint>>([
		{
			id: 0,
			name: "Loading",
			author: "Loading",
			canDelete: false,
			comments: [],
		},
	]);
	const points_reference = useRef(points);
	const [loaded, setLoaded] = useState({
		points: false,
		point: false,
	});
	const [open, setOpen] = useState({
		new_point_modal: false,
	});
	const [newPoint, setNewPoint] = useState<MNewPoint>({
		title: "",
		description: "",
	});
	const [newComment, setNewComment] = useState<MNewComment>({
		content: "",
	});

	useEffect(() => {
		props.api.get(`/v2/meetings/${props.id}/get`).then(({ data }) => {
			if (data.success) {
				setPoints(data.points);
				setLoaded({ ...loaded, points: true });
			} else {
				setPoints([
					{
						id: 0,
						name: "Failed To Load Meeting",
						author: "Reload the page to try again.",
						comments: [],
					},
				]);
			}
		});

		let channel = props.pusher.subscribe("meetings");
		channel.bind("addPoint", (data: MPoint) => {
			handleAddPoint(data);
		});
		channel.bind("deletePoint", (data: MPointDelete) => {
			handleDeletePoint(data);
		});
		channel.bind("addComment", (data: MPointComment) => {
			handleAddComment(data);
		});
		return () => {
			channel.unbind("addPoint");
			channel.unbind("deletePoint");
			channel.unbind("addComment");
			props.pusher.unsubscribe("meetings");
		};
	}, []);

	// This is the problem child
	useEffect(() => {
		point_reference.current = point;
		points_reference.current = points;
	}, [point, points]);

	function handleAddPoint(data: MPoint) {
		const pts = points_reference.current;
		if (parseInt(data.meetingID!.toString()) === parseInt(props.id)) {
			setPoints([{ name: data.name, author: data.author, id: data.id, comments: [] }, ...pts]);
		}
	}

	function handleAddComment(data: MPointComment) {
		const p = point_reference.current;
		if (p && parseInt(data.pointID.toString()) === parseInt(p.id.toString())) {
			setPoint({
				...p,
				comments: [{ content: data.content, author: data.author, id: data.id }, ...p.comments],
			});
		}
	}

	function handleDeletePoint(data: MPointDelete) {
		const pts = points_reference.current;
		const pt = point_reference.current;
		let del = pts.findIndex((x) => x.id === parseInt(data.deleteID));
		if (del === -1 || !pt) return;
		pts.splice(del, 1);
		setPoints([...pts]);
		if (points.length === 0) {
			setPoints([]);
		}

		if (parseInt(pt.id.toString()) === parseInt(data.deleteID)) {
			setLoaded({ points: true, point: false });
		}
	}

	function loadPointDetails(id: number) {
		props.api.get(`/v2/meetings/${props.id}/point/${id}/get`).then(({ data }) => {
			if (data.code === 200) {
				setPoint({ ...data.response });
				setLoaded({ ...loaded, point: true });
			}
		});
	}

	function toggleCreateNewPoint() {
		setOpen({
			new_point_modal: !open.new_point_modal,
		});
	}

	function handleChangePTitle(d: React.ChangeEvent<HTMLInputElement>) {
		setNewPoint({
			...newPoint,
			title: d.target.value,
		});
	}

	function handleChangePDescription(d: React.ChangeEvent<HTMLTextAreaElement>) {
		setNewPoint({
			...newPoint,
			description: d.target.value,
		});
	}

	function handleChangeCContent(d: React.ChangeEvent<HTMLInputElement>) {
		setNewComment({
			...newComment,
			content: d.target.value,
		});
	}

	function handleFormSubmit(e: React.FormEvent<HTMLFormElement>) {
		e.preventDefault();
		if (newPoint.description !== "" && newPoint.title !== "") {
			props.api
				.post(`/v2/meetings/${props.id}/point/add`, {
					...newPoint,
				})
				.then(() => {
					setOpen({ new_point_modal: false });
					setNewPoint({ title: "", description: "" });
				});
		}
	}

	function handleNewCommentSubmit(e: React.FormEvent<HTMLFormElement>) {
		e.preventDefault();
		if (point && newComment.content !== "") {
			props.api
				.post(`/v2/meetings/${props.id}/point/${point.id}/comment`, {
					...newComment,
				})
				.then(() => {
					setNewComment({ content: "" });
				});
		}
	}

	function deletePoint(id: number) {
		// Handled by Websockets
		props.api.post(`/v2/meetings/${props.id}/point/${id}/delete`).catch(() => {
			console.error("Failed to delete point", id, "from meeting", props.id);
		});
	}

	return (
		<div>
			<button onClick={toggleCreateNewPoint} className={`newPointBtn ${open.new_point_modal ? "open" : ""}`}>
				+
			</button>
			<div className={"grid new meeting "}>
				<div className="grid__col grid__col--2-of-6">
					<div className="gridLeftPadding">
						<h1 className="info-title new">Meeting On {props.date}</h1>
						<div className="selectionPanel meetingPanelHeight">
							{loaded.points ? (
								points.map((point, k) => (
									<li
										key={point.id}
										className="selectionTab points"
										onClick={() => loadPointDetails(point.id)}
										onKeyDown={(e) => {
											if (e.key === "Enter") {
												loadPointDetails(point.id);
											}
										}}
									>
										<h2 dangerouslySetInnerHTML={{ __html: point.name }} />
										<p>{point.author}</p>
										{point.canDelete && (
											<span
												className="delPoint"
												title="Double Click To Delete."
												onDoubleClick={() => deletePoint(point.id)}
											>
												<i className="fas fa-trash" />
											</span>
										)}
									</li>
								))
							) : (
								<Loading />
							)}
							{loaded.points && points.length <= 0 && (
								<div>
									<h3>No Points Added Yet.</h3>
									<p>
										Add a point by clicking the <b>+</b> button.
									</p>
								</div>
							)}
						</div>
					</div>
				</div>
				<div className="grid__col grid__col--4-of-6">
					<div className="infoPanelContainer">
						<div className="infoPanel">
							{point && loaded.point ? (
								<div className="pointControl" style={{ left: "66.15%" }}>
									<h1
										dangerouslySetInnerHTML={{
											__html: point.name,
										}}
									/>
									<small>{point.author}</small>
									<p
										className="description"
										dangerouslySetInnerHTML={{
											__html: point.description ?? "No Description Provided.",
										}}
									/>
									<div className="comments">
										{point.comments?.length ? (
											point.comments.map((comment) => (
												<li key={comment.id} title={comment.created_at}>
													<p className="author">
														{comment.author?.rank} {comment.author?.displayName}
													</p>
													<p
														className="content"
														dangerouslySetInnerHTML={{
															__html: comment.content ?? "No Content Provided.",
														}}
													/>
												</li>
											))
										) : (
											<h2>No Comments Found</h2>
										)}
									</div>
									<form onSubmit={handleNewCommentSubmit}>
										<div className="field addComment">
											<div className="fieldTitle">
												Your Comment <button className="postComment">Post</button>
											</div>
											<input
												type="text"
												className="fieldInput"
												value={newComment.content}
												placeholder="Your Comment"
												onChange={handleChangeCContent}
											/>
										</div>
									</form>
								</div>
							) : (
								<h2>Select Meeting Point</h2>
							)}
						</div>
					</div>
				</div>
			</div>
			<div className={`drawer ${open.new_point_modal ? "open" : ""}`}>
				<h1>New Point</h1>
				<form onSubmit={handleFormSubmit}>
					<div className="field">
						<p className="fieldTitle">Point Title</p>
						<input
							className="fieldInput"
							value={newPoint.title}
							type="text"
							placeholder="Point Title"
							onChange={handleChangePTitle}
						/>
					</div>
					<div className="field">
						<p className="fieldTitle">Point Description</p>
						<textarea
							className="fieldTextarea pdesc"
							placeholder="Point Description"
							onChange={handleChangePDescription}
							value={newPoint.description}
						/>
					</div>
					<button type="submit" className="createPointBtn">
						Create
					</button>
				</form>
			</div>
		</div>
	);
};
