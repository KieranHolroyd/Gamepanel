class Meeting extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      point: {},
      points: [{ id: "0", name: "Loading", author: "Loading" }],
      loaded: {
        points: false,
        point: false,
      },
      open: {
        createNewPoint: false,
      },
      new: {
        point: {
          title: "",
          description: "",
        },
        comment: {
          content: "",
        },
      },
    };

    this.handleMeeting = this.handleMeeting.bind(this);
    this.handleGetPoint = this.handleGetPoint.bind(this);
    this.toggleCreateNewPoint = this.toggleCreateNewPoint.bind(this);
    this.handleChangePTitle = this.handleChangePTitle.bind(this);
    this.handleChangePDescription = this.handleChangePDescription.bind(this);
    this.handleChangeCContent = this.handleChangeCContent.bind(this);
    this.handleFormSubmit = this.handleFormSubmit.bind(this);
    this.handleNewCommentSubmit = this.handleNewCommentSubmit.bind(this);
    this.handleAddedPoint = this.handleAddedPoint.bind(this);
    this.handleAddedComment = this.handleAddedComment.bind(this);

    let pusher = new Pusher("123979dbead391bef050", {
      cluster: "eu",
      forceTLS: true,
    });

    let channel = pusher.subscribe("meetings");
    channel.bind("addPoint", this.handleAddPoint);
    channel.bind("deletePoint", this.handleDeletePoint);
    channel.bind("addComment", this.handleAddComment);
  }

  componentDidMount() {
    this.loadMeeting();
  }

  loadMeeting() {
    apiclient
      .get(`/api/v2/meetings/${this.props.id}/get`)
      .then(this.handleMeeting);
  }

  handleMeeting({ data }) {
    if (data.success) {
      let state = data.points;
      state = { points: [...state], loaded: { points: true } };
      this.setState(state);
    } else {
      this.setState({
        loaded: { points: true },
        points: [
          {
            id: "0",
            name: "Failed To Load Meeting",
            author: "Reload the page to try again.",
          },
        ],
      });
    }
  }

  handleAddPoint = (data) => {
    let id = this.props.id;

    if (parseInt(data.meetingID) === parseInt(id)) {
      let prevState = this.state.points;

      let newState = [
        { name: data.name, author: data.author, id: data.id },
        ...prevState,
      ];

      this.setState({
        points: newState,
      });
    }
  };

  handleAddComment = (data) => {
    let id = this.state.point.id;

    if (parseInt(data.pointID) === parseInt(id)) {
      this.setState({
        point: {
          ...this.state.point,
          comments: [
            { content: data.content, author: data.author, id: data.id },
            ...this.state.point.comments,
          ],
        },
      });
    }
  };

  handleDeletePoint = (data) => {
    let del = this.state.points.findIndex((x) => x.id === data.deleteID);
    let newPoints = this.state.points;
    newPoints.splice(del, 1);
    this.setState({ points: newPoints });

    console.log(this.state.point.id, data.deleteID);
    if (parseInt(this.state.point.id) === parseInt(data.deleteID)) {
      this.setState({ loaded: { ...this.state.loaded, point: false } });
    }
  };

  loadPointDetails(id) {
    $.get(`/api/v1/getPointNew?pointID=${id}`, this.handleGetPoint);
  }

  handleGetPoint(data) {
    data = JSON.parse(data);
    if (data.code === 200) {
      this.setState({
        point: data.response,
        loaded: { ...this.state.loaded, point: true },
      });
    }
  }

  toggleCreateNewPoint() {
    this.setState({
      open: { createNewPoint: !this.state.open.createNewPoint },
    });
  }

  handleChangePTitle(d) {
    this.setState({
      new: {
        ...this.state.new,
        point: { ...this.state.new.point, title: d.target.value },
      },
    });
  }

  handleChangePDescription(d) {
    this.setState({
      new: {
        ...this.state.new,
        point: { ...this.state.new.point, description: d.target.value },
      },
    });
  }

  handleChangeCContent(d) {
    this.setState({
      new: { ...this.state.new, comment: { content: d.target.value } },
    });
  }

  handleFormSubmit(e) {
    e.preventDefault();
    if (
      this.state.new.point.description !== "" &&
      this.state.new.point.title !== ""
    ) {
      $.post(
        `/api/v2/meetings/${this.props.id}/point/add`,
        {
          ...this.state.new.point,
        },
        this.handleAddedPoint
      );
    }
  }

  handleNewCommentSubmit(e) {
    e.preventDefault();
    if (this.state.new.comment.content !== "") {
      $.post(
        "/api/v1/addCommentNew",
        {
          ...this.state.new.comment,
          pointID: this.state.point.id,
        },
        this.handleAddedComment
      );
    }
  }

  handleAddedPoint() {
    this.setState({
      new: { ...this.state.new, point: { title: "", description: "" } },
    });
  }

  handleAddedComment() {
    this.setState({ new: { ...this.state.new, comment: { content: "" } } });
  }

  deletePoint(id) {
    $.post(
      "/api/v1/deletePoint",
      {
        pointID: id,
      },
      this.handleDeletedPoint
    );
  }

  render() {
    return (
      <div>
        <button
          onClick={this.toggleCreateNewPoint}
          className={
            "newPointBtn " + (this.state.open.createNewPoint ? "open" : "")
          }
        >
          +
        </button>
        <div
          className={
            "grid new meeting " + (this.state.open.createNewPoint ? "open" : "")
          }
        >
          <div className="grid__col grid__col--2-of-6">
            <div className="gridLeftPadding">
              <h1 className="info-title new">Meeting On {this.props.date}</h1>
              <div className="selectionPanel meetingPanelHeight">
                {this.state.loaded.points ? (
                  this.state.points.map((point) => (
                    <li
                      key={point.id}
                      className="selectionTab points"
                      onClick={() => this.loadPointDetails(point.id)}
                    >
                      <h2 dangerouslySetInnerHTML={{ __html: point.name }} />
                      <p>{point.author}</p>
                      <span
                        className="delPoint"
                        title="Double Click To Delete."
                        onDoubleClick={() => this.deletePoint(point.id)}
                      >
                        Delete Point
                      </span>
                    </li>
                  ))
                ) : (
                  <img src="/img/loadw.svg" />
                )}
                {this.state.loaded.points && this.state.points.length === 0 && (
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
                {this.state.loaded.point ? (
                  <div className="pointControl" style={{ left: "66.15%" }}>
                    <h1
                      dangerouslySetInnerHTML={{
                        __html: this.state.point.name,
                      }}
                    />
                    <small>{this.state.point.author}</small>
                    <p
                      className="description"
                      dangerouslySetInnerHTML={{
                        __html: this.state.point.description,
                      }}
                    />
                    <div className="comments">
                      {this.state.point.comments.length ? (
                        this.state.point.comments.map((comment) => (
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
                    <form onSubmit={this.handleNewCommentSubmit}>
                      <div className="field addComment">
                        <div className="fieldTitle">
                          Your Comment{" "}
                          <button className="postComment">Post</button>
                        </div>
                        <input
                          type="text"
                          className="fieldInput"
                          value={this.state.new.comment.content}
                          placeholder="Your Comment"
                          onChange={this.handleChangeCContent}
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
        <div
          className={"drawer " + (this.state.open.createNewPoint ? "open" : "")}
        >
          <h1>New Point</h1>
          <form onSubmit={this.handleFormSubmit}>
            <div className="field">
              <p className="fieldTitle">Point Title</p>
              <input
                className="fieldInput"
                value={this.state.new.point.title}
                type="text"
                placeholder="Point Title"
                onChange={this.handleChangePTitle}
              />
            </div>
            <div className="field">
              <p className="fieldTitle">Point Description</p>
              <textarea
                className="fieldTextarea pdesc"
                placeholder="Point Description"
                onChange={this.handleChangePDescription}
                value={this.state.new.point.description}
              />
            </div>
            <button type="submit" className="createPointBtn">
              Create
            </button>
          </form>
        </div>
      </div>
    );
  }
}
