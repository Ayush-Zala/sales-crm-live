import { Circle } from "@mui/icons-material";
import { Fragment } from "react";

const OnlineStatusComponent = ({ isOnline }) => {
    return (
        <Fragment>
            {isOnline ? (
                <Circle color="success" fontSize="small" />
            ) : (
                <Circle color="error" fontSize="small" />
            )}
        </Fragment>
    );
};

export default OnlineStatusComponent;
