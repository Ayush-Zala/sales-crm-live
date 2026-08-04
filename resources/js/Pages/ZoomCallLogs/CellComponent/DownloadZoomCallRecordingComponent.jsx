import { DownloadingRounded } from "@mui/icons-material";
import { CircularProgress, IconButton, Tooltip } from "@mui/material";
import { useState } from "react";
import toast from "react-hot-toast";

const DownloadZoomCallRecordingComponent = ({ callId }) => {
    const [loading, setLoading] = useState(false);

    const handleDownloadRecording = async () => {
        setLoading(true);

        await fetch(route("zoom.getrecordingurl", { callId }))
            .then((response) => response.json())
            .then((data) => {
                window.open(data.original.file_url, "_blank");
            })
            .catch((error) => {
                console.log(error);
                toast.error("Failed to download recording");
            })
            .finally(() => {
                setLoading(false);
            });
    };

    return (
        <Tooltip placement="right" title="Download Recording" arrow>
            <IconButton onClick={handleDownloadRecording} disabled={loading}>
                {loading ? (
                    <CircularProgress size={16} color="error" />
                ) : (
                    <DownloadingRounded color="error" fontSize="small" />
                )}
            </IconButton>
        </Tooltip>
    );
};

export default DownloadZoomCallRecordingComponent;
