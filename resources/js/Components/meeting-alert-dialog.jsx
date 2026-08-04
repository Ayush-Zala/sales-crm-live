import { BusinessRounded } from "@mui/icons-material";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    IconButton,
    Link,
    Tooltip,
    Typography,
} from "@mui/material";
import { utcToZonedTime, zonedTimeToUtc } from "date-fns-tz";
import { Calendar } from "lucide-react";
import { useCallback, useEffect, useState } from "react";

const MeetingAlertDialog = () => {
    const [meetings, setMeetings] = useState([]);
    const [open, setOpen] = useState(false);
    const [currentMeeting, setCurrentMeeting] = useState(null);
    const [remainingTime, setRemainingTime] = useState("");
    const [notified, setNotified] = useState([]); // Track notified meeting IDs and intervals

    const IST_TIMEZONE = "Asia/Kolkata"; // Define IST timezone

    // Format a given date to IST
    // Convert a time from any timezone to IST
    const convertToIST = (dateString, fromTimezone) => {
        // Convert the given time in its original timezone to UTC
        const utcDate = zonedTimeToUtc(dateString, fromTimezone);
        // Convert UTC to IST
        return utcToZonedTime(utcDate, IST_TIMEZONE);
    };

    const fetchMeetings = useCallback(async () => {
        if (meetings.length > 0) return; // Prevent redundant calls
        try {
            fetch(route("calendar.todaysevents"))
                .then((response) => response.json())
                .then((data) => {
                    setMeetings(data);
                });
        } catch (error) {
            console.error("Failed to fetch meetings:", error);
        }
    }, [meetings.length]);

    useEffect(() => {
        fetchMeetings(); // Fetch meetings once when the component mounts
    }, []);

    useEffect(() => {
        if (!meetings.length) return; // Exit if no meetings are set

        const intervals = [15, 5, 2, 0]; // Minutes before target time

        const checkMeetings = () => {
            const now = utcToZonedTime(new Date(), IST_TIMEZONE);

            const filteredMeetings = meetings.filter((meeting, index) => {
                const meetingTime = convertToIST(
                    meeting.start_date,
                    meeting.timezone
                );
                const diff = Math.round((meetingTime - now) / 1000 / 60); // Difference in minutes
                const notifyKey = `${index}-${diff}`; // Unique key for meeting and interval

                // console.log("Meeting:", meeting.title, "Diff:", diff);
                // console.log(meetingTime);

                if (intervals.includes(diff) && !notified.includes(notifyKey)) {
                    setCurrentMeeting(meeting);
                    setRemainingTime(
                        diff > 0 ? `${diff} minutes` : "Starting now"
                    );
                    setOpen(true);
                    setNotified((prev) => [...prev, notifyKey]); // Mark this meeting at this interval as notified
                }

                // Keep the meeting if it hasn't passed
                return meetingTime > now;
            });

            setMeetings(filteredMeetings);
        };

        const timer = setInterval(checkMeetings, 1000); // Check every second
        return () => clearInterval(timer); // Cleanup timer
    }, [meetings, notified]);

    return (
        <Dialog
            open={open}
            maxWidth="sm"
            fullWidth
            onClose={(_, reason) =>
                reason !== "backdropClick" && setOpen(false)
            }
        >
            <DialogTitle
                sx={{
                    display: "flex",
                    justifyContent: "space-between",
                    alignItems: "center",
                }}
            >
                Meeting Reminder
                {currentMeeting?.company_id && (
                    <Tooltip title="View this company">
                        <IconButton
                            LinkComponent={Link}
                            href={route(
                                "account.view",
                                currentMeeting?.company_id
                            )}
                            target="_blank"
                            sx={{ color: "#1976d2" }}
                        >
                            <BusinessRounded />
                        </IconButton>
                    </Tooltip>
                )}
            </DialogTitle>
            <DialogContent
                sx={{
                    display: "flex",
                    flexDirection: "column",
                    alignItems: "center",
                    padding: "20px",
                    backgroundColor: "#ffffff",
                    borderRadius: "8px",
                }}
                dividers
            >
                <Calendar
                    style={{
                        color: "#1976d2",
                        // fontSize: "40px",
                        marginBottom: "10px",
                    }}
                    size={50}
                />
                <Typography
                    variant="h6"
                    sx={{
                        fontWeight: "bold",
                        textAlign: "center",
                        marginBottom: "10px",
                    }}
                >
                    {currentMeeting?.title}
                </Typography>
                <Typography
                    variant="body1"
                    sx={{ color: "#757575", marginBottom: "10px" }}
                >
                    Start Time:
                    {new Date(currentMeeting?.start_date).toLocaleString()} (
                    {currentMeeting?.timezone})
                </Typography>
                <Typography
                    variant="body1"
                    sx={{
                        color: "#ff5722",
                        fontWeight: "bold",
                        marginBottom: "10px",
                    }}
                >
                    Remaining Time: {remainingTime}
                </Typography>
                {currentMeeting?.zoomMeetingUrl && (
                    <Typography
                        variant="body2"
                        sx={{
                            color: "#1976d2",
                            cursor: "pointer",
                            ":hover": {
                                color: "#1565c0",
                                textDecoration: "underline",
                            },
                        }}
                        onClick={() =>
                            window.open(currentMeeting.zoomMeetingUrl, "_blank")
                        }
                    >
                        Join Zoom Meeting
                    </Typography>
                )}
            </DialogContent>
            <DialogActions sx={{ justifyContent: "center", py: 2 }}>
                <Button
                    variant="contained"
                    sx={{
                        px: 10,
                        backgroundColor: "#4caf50",
                        "&:hover": {
                            backgroundColor: "#43a047",
                        },
                    }}
                    onClick={() => setOpen(false)}
                >
                    OK
                </Button>
            </DialogActions>
        </Dialog>
    );
};

export default MeetingAlertDialog;
