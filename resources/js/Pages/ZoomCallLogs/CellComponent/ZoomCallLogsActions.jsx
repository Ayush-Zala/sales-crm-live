import React from "react";
import DownloadZoomCallRecordingComponent from "./DownloadZoomCallRecordingComponent";
import { Stack } from "@mui/material";

const ZoomCallLogsActions = ({ row }) => {
    return (
        <Stack direction="row" spacing={1}>
            {row.file_url && (
                <DownloadZoomCallRecordingComponent callId={row.call_id} />
            )}
        </Stack>
    );
};

export default ZoomCallLogsActions;
