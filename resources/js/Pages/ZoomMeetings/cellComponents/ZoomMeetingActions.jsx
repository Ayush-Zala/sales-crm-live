import { AudioFileRounded, LinkRounded } from "@mui/icons-material";
import { IconButton, Link, Tooltip } from "@mui/material";

const ZoomMeetingActions = ({ row }) => {
    const handleDownload = async (url) => {
        try {
            const response = await fetch(url);
            const blob = await response.blob();
            const link = document.createElement("a");

            link.href = URL.createObjectURL(blob);
            link.download = "transcript.txt"; // File name for download

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch (error) {
            console.error("Download failed:", error);
        }
    };

    return (
        <>
            {row.share_url && (
                <Tooltip title="Meeting URL" placement="left" arrow>
                    <IconButton
                        color="primary"
                        LinkComponent={Link}
                        href={row.share_url}
                        target="_blank"
                    >
                        <LinkRounded />
                    </IconButton>
                </Tooltip>
            )}

            {row.audio_transcript && (
                <Tooltip
                    title="Download Audio Transcript"
                    placement="left"
                    arrow
                >
                    <IconButton
                        color="primary"
                        onClick={() =>
                            handleDownload(row.audio_file_script_url)
                        }
                    >
                        <AudioFileRounded />
                    </IconButton>
                </Tooltip>
            )}
        </>
    );
};

export default ZoomMeetingActions;
