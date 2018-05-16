import { h, render, Component } from "preact";
import http from "./http.js";
import PreviewBlock from "./preview-block.component.js";
import FileGrid from "./file-grid.component.js";

export default class MessageCard extends Component {

    constructor(props) {
        super(props);
        this.state = {
            url: props.url,
            regex: {
                link: /\b(https?:\/\/[^\s]+)\b/gi
            },
            message: {},
            author: {},
            preview: {},
            displayedChildren: [],
        }
        http.get(this.state.url).then(msg => {
            this.setState({
                message: msg,
                displayedChildren: msg.children
            });
            http.get(msg.author).then(author => {
                this.setState({author: author});
            });
            if (msg.data) {
                let previewUrl = msg.data.match(this.state.regex.link);
                if (previewUrl[0]) {
                    http.post("/api/links/by_url", {
                        url: previewUrl[0]
                    }).then(r => this.setState({preview: r}));
                }
            }
        });
    }

    displayMessageText() {
        return {
            __html: this.state.message.data.replace(this.state.regex.link, "<a href=\"$1\">$1</a>")
        };
    }

    displayDate(timestamp) {
        if (!timestamp) {
            return null;
        }
        let date = new Date(timestamp*1000);
        return date.getDate() + "/" + (date.getMonth() + 1) + "/" + date.getFullYear();
    }

    render() {
        return (
            <div class="card mb-1">
                <div class="card-header d-flex">
                    {
                        this.state.author &&
                        this.state.author.avatar &&
                        <img class="rounded w-3" src={ http.crop(this.state.author.avatar, 50, 50) }/>
                    }
                    <div class="d-flex flex-column">
                        <span class="capitalize ml-1">{ this.state.author.name }</span>
                        <span class="ml-1">{ this.displayDate(this.state.message.createdAt) }</span>
                    </div>
                </div>
                <div class="card-body">
                    { this.state.message.data && <p class="card-text" dangerouslySetInnerHTML={this.displayMessageText()}></p> }
                    <PreviewBlock {...this.state.preview} />
                    { this.state.message.files && <FileGrid files={this.state.message.files}/> }
                </div>
                {
                    this.state.displayedChildren.length > 0 &&
                    (
                        <div class="card-footer">
                            { this.state.displayedChildren.map(e => <MessageCard url={e} /> ) }
                        </div>
                    )
                }
            </div>
        );
    }
}