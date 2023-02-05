import React, { useEffect, useRef, useState } from "react";

export const Meeting = (props) => {
  const [point, setPoint] = useState({});
  const point_reference = useRef(point);

  const [points, setPoints] = useState([
    { id: "0", name: "Loading", author: "Loading", canDelete: false },
  ]);
  const points_reference = useRef(points);
  const [loaded, setLoaded] = useState({
    points: false,
    point: false,
  });
  const [open, setOpen] = useState({
    createNewPoint: false,
  });
  const [newPoint, setNewPoint] = useState({
    title: "",
    description: "",
  });
  const [newComment, setNewComment] = useState({
    content: "",
  });

  useEffect(() => {
    apiclient.get(`/api/v2/meetings/${props.id}/get`).then(({ data }) => {
      if (data.success) {
        setPoints(data.points);
        setLoaded({ ...loaded, points: true });
      } else {
        setPoints([
          {
            error: true,
            id: "0",
            name: "Failed To Load Meeting",
            author: "Reload the page to try again.",
          },
        ]);
      }
    });

    let channel = props.pusher.subscribe("meetings");
    channel.bind("addPoint", (data) => {
      handleAddPoint(data);
    });
    channel.bind("deletePoint", (data) => {
      handleDeletePoint(data);
    });
    channel.bind("addComment", (data) => {
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

  function handleAddPoint(data) {
    const pts = points_reference.current;
    if (parseInt(data.meetingID) === parseInt(props.id)) {
      setPoints([
        { name: data.name, author: data.author, id: data.id },
        ...pts,
      ]);
    }
  }

  function handleAddComment(data) {
    const p = point_reference.current;
    if (parseInt(data.pointID) === parseInt(p.id)) {
      setPoint({
        ...p,
        comments: [
          { content: data.content, author: data.author, id: data.id },
          ...p.comments,
        ],
      });
    }
  }

  function handleDeletePoint(data) {
    const pts = points_reference.current;
    const pt = point_reference.current;
    let del = pts.findIndex((x) => x.id === parseInt(data.deleteID));
    if (del === -1) return;
    pts.splice(del, 1);
    setPoints([...pts]);
    if (points.length === 0) {
      setPoints([]);
    }

    if (parseInt(pt.id) === parseInt(data.deleteID)) {
      setLoaded({ points: true, point: false });
    }
  }

  function loadPointDetails(id) {
    apiclient
      .get(`/api/v2/meetings/${props.id}/point/${id}/get`)
      .then(({ data }) => {
        if (data.code === 200) {
          setPoint({ ...data.response });
          setLoaded({ ...loaded, point: true });
        }
      });
  }

  function toggleCreateNewPoint() {
    setOpen({
      createNewPoint: !open.createNewPoint,
    });
  }

  function handleChangePTitle(d) {
    setNewPoint({
      ...newPoint,
      title: d.target.value,
    });
  }

  function handleChangePDescription(d) {
    setNewPoint({
      ...newPoint,
      description: d.target.value,
    });
  }

  function handleChangeCContent(d) {
    setNewComment({
      ...newComment,
      content: d.target.value,
    });
  }

  function handleFormSubmit(e) {
    e.preventDefault();
    if (newPoint.description !== "" && newPoint.title !== "") {
      $.post(
        `/api/v2/meetings/${props.id}/point/add`,
        {
          ...newPoint,
        },
        () => {
          setNewPoint({ title: "", description: "" });
        }
      );
    }
  }

  function handleNewCommentSubmit(e) {
    e.preventDefault();
    if (newComment.content !== "") {
      $.post(
        `/api/v2/meetings/${props.id}/point/${point.id}/comment`,
        {
          ...newComment,
        },
        () => {
          setNewComment({ content: "" });
        }
      );
    }
  }

  function deletePoint(id) {
    // Handled by Websockets
    $.post(`/api/v2/meetings/${props.id}/point/${id}/delete`);
  }

  return (
    <div>
      <button
        onClick={toggleCreateNewPoint}
        className={"newPointBtn " + (open.createNewPoint ? "open" : "")}
      >
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
                    key={k}
                    className="selectionTab points"
                    onClick={() => loadPointDetails(point.id)}
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
                <img src="/img/loadw.svg" />
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
              {loaded.point ? (
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
                      __html: point.description,
                    }}
                  />
                  <div className="comments">
                    {point.comments !== undefined && point.comments.length ? (
                      point.comments.map((comment) => (
                        <li key={comment.id} title={comment.created_at}>
                          <p className="author">
                            {comment.author.rank} {comment.author.displayName}
                          </p>
                          <p
                            className="content"
                            dangerouslySetInnerHTML={{
                              __html: comment.content,
                            }}
                          ></p>
                        </li>
                      ))
                    ) : (
                      <h2>No Comments Found</h2>
                    )}
                  </div>
                  <form onSubmit={handleNewCommentSubmit}>
                    <div className="field addComment">
                      <div className="fieldTitle">
                        Your Comment{" "}
                        <button className="postComment">Post</button>
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
      <div className={"drawer " + (open.createNewPoint ? "open" : "")}>
        <h1>New Point</h1>
        <form onSubmit={handleFormSubmit}>
          <div className="field">
            <p className="fieldTitle">Point Title</p>
            <input
              className="fieldInput"
              value={point.title}
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
