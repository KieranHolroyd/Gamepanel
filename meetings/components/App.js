class App extends React.Component {
    constructor(props) {
        super(props);

        this.state = {

        }
    }

    render() {
        return (
            <Meeting date={this.props.meetingDate} id={this.props.meetingID} />
        )
    }
}