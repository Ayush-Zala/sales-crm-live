import { Stack, Typography, Tooltip } from "@mui/material";
import { format, utcToZonedTime } from "date-fns-tz";
import { useEffect, useState } from "react";

const TimeZoneList = () => {
    return (
        <Stack direction="row" spacing={1.5}>
            <TimeZoneItem timeZone="America/New_York" abbreviation="EST" />
            <TimeZoneItem timeZone="America/Merida" abbreviation="CST" />
            <TimeZoneItem timeZone="America/Denver" abbreviation="MST" />
            <TimeZoneItem timeZone="America/Los_Angeles" abbreviation="PST" />
            <TimeZoneItem timeZone="Asia/Kolkata" abbreviation="IST" />
        </Stack>
    );
};

export default TimeZoneList;

const TimeZoneItem = ({ abbreviation, timeZone }) => {
    const [time, setTime] = useState(utcToZonedTime(new Date(), timeZone));

    useEffect(() => {
        const intervalId = setInterval(() => {
            setTime(utcToZonedTime(new Date(), timeZone));
        }, 1000);

        return () => clearInterval(intervalId);
    });

    return (
        <Stack direction="column" alignItems="end">
            <Tooltip
                arrow
                placement="left"
                title={
                    abbreviation === "EST"
                        ? "Eastern Standard Time"
                        : abbreviation === "PST"
                        ? "Pacific Standard Time"
                        : abbreviation === "CST"
                        ? "Central Standard Time"
                        : abbreviation === "MST"
                        ? "Mountain Standard Time"
                        : abbreviation
                }
            >
                <Typography noWrap fontSize="10px" sx={{ cursor: "pointer" }}>
                    {abbreviation}
                </Typography>
            </Tooltip>

            <Tooltip arrow placement="bottom-end" title={format(time, "PPPPp")}>
                <Typography
                    noWrap
                    fontSize="small"
                    sx={{ cursor: "pointer", color: "yellow" }}
                >
                    {format(time, "p")}
                </Typography>
            </Tooltip>
        </Stack>
    );
};
