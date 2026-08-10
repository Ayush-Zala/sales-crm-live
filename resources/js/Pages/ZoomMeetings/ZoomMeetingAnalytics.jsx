import React from "react";
import { Grid, Card, Typography, Box, alpha, useTheme } from "@mui/material";
import EventNoteIcon from "@mui/icons-material/EventNote";
import GroupIcon from "@mui/icons-material/Group";
import AccessTimeIcon from "@mui/icons-material/AccessTime";
import GroupsIcon from "@mui/icons-material/Groups";
import TimerIcon from "@mui/icons-material/Timer";
import TrendingUpIcon from "@mui/icons-material/TrendingUp";
import TrendingDownIcon from "@mui/icons-material/TrendingDown";
import DragHandleIcon from "@mui/icons-material/DragHandle";
import ZoomMeetingAdvancedCharts from "./ZoomMeetingAdvancedCharts";

const formatDuration = (minutes) => {
    if (!minutes) return "0m";
    const h = Math.floor(minutes / 60);
    const m = Math.floor(minutes % 60);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
};

const MetricCard = ({ title, value, prevValue, periodLabel, icon, color, isDuration = false }) => {
    const theme = useTheme();
    const isUp = value > prevValue;
    const isDown = value < prevValue;
    const isSame = value === prevValue;

    // Calculate percentage difference
    let diff = 0;
    if (prevValue > 0) {
        diff = ((value - prevValue) / prevValue) * 100;
    } else if (value > 0) {
        diff = 100;
    }

    const diffFormatted = Math.abs(diff).toFixed(1) + "%";
    
    // Is it a positive trend? Usually more meetings, more participants, etc is good.
    let isPositiveTrend = isUp;

    const trendColor = isSame ? "text.secondary" : (isPositiveTrend ? "success.main" : "error.main");

    return (
        <Card
            elevation={0}
            sx={{
                p: 2,
                borderRadius: 3,
                height: "100%",
                border: "1px solid",
                borderColor: "divider",
                bgcolor: "background.paper",
                transition: "all 0.3s cubic-bezier(0.4, 0, 0.2, 1)",
                position: "relative",
                overflow: "hidden",
                "&:hover": {
                    transform: "translateY(-4px)",
                    boxShadow: `0 12px 24px -10px ${alpha(theme.palette[color].main, 0.4)}`,
                    borderColor: alpha(theme.palette[color].main, 0.3),
                    "& .icon-wrapper": {
                        transform: "scale(1.1) rotate(5deg)",
                    }
                }
            }}
        >
            <Box
                sx={{
                    position: "absolute",
                    top: -15,
                    right: -15,
                    width: 100,
                    height: 100,
                    borderRadius: "50%",
                    background: `radial-gradient(circle, ${alpha(theme.palette[color].main, 0.1)} 0%, ${alpha(theme.palette[color].main, 0)} 70%)`,
                    zIndex: 0
                }}
            />
            <Box sx={{ position: "relative", zIndex: 1 }}>
                <Box display="flex" justifyContent="space-between" alignItems="flex-start" mb={1}>
                    <Typography variant="overline" color="text.secondary" fontWeight={600} lineHeight={1.2}>
                        {title}
                    </Typography>
                    <Box
                        className="icon-wrapper"
                        sx={{
                            p: 1,
                            borderRadius: "50%",
                            bgcolor: alpha(theme.palette[color].main, 0.1),
                            color: theme.palette[color].main,
                            display: "flex",
                            transition: "all 0.3s ease",
                        }}
                    >
                        {icon}
                    </Box>
                </Box>
                <Typography variant="h4" fontWeight="bold" color="text.primary" mb={1.5}>
                    {isDuration ? formatDuration(value) : value}
                </Typography>
                <Box display="flex" flexDirection="column" gap={0.5}>
                    {!isSame && (
                        <Box display="flex" alignItems="center" gap={0.5}>
                            {isUp && <TrendingUpIcon sx={{ fontSize: 16, color: trendColor }} />}
                            {isDown && <TrendingDownIcon sx={{ fontSize: 16, color: trendColor }} />}
                            <Typography variant="caption" sx={{ color: trendColor, fontWeight: 600, whiteSpace: "nowrap" }}>
                                {(isUp ? "+" : "-")}{diffFormatted}
                            </Typography>
                        </Box>
                    )}
                    <Typography variant="caption" color="text.secondary" sx={{ whiteSpace: "nowrap" }}>
                        vs {periodLabel}
                    </Typography>
                </Box>
            </Box>
        </Card>
    );
};

const ZoomMeetingAnalytics = ({ analytics }) => {
    if (!analytics || !analytics.current) return null;

    const { current, previous, period_label } = analytics;

    return (
        <Box mb={4}>
            <Grid container spacing={2} sx={{ mb: 3 }}>
                <Grid item xs={12} sm={6} md={2.4}>
                    <MetricCard
                        title="TOTAL MEETINGS"
                        value={current.total_meetings}
                        prevValue={previous.total_meetings}
                        periodLabel={period_label}
                        icon={<EventNoteIcon fontSize="small" />}
                        color="primary"
                    />
                </Grid>
                <Grid item xs={12} sm={6} md={2.4}>
                    <MetricCard
                        title="TOTAL PARTICIPANTS"
                        value={current.total_participants}
                        prevValue={previous.total_participants}
                        periodLabel={period_label}
                        icon={<GroupIcon fontSize="small" />}
                        color="success"
                    />
                </Grid>
                <Grid item xs={12} sm={6} md={2.4}>
                    <MetricCard
                        title="TOTAL MEETING HOURS"
                        value={current.total_meeting_hours}
                        prevValue={previous.total_meeting_hours}
                        periodLabel={period_label}
                        icon={<AccessTimeIcon fontSize="small" />}
                        color="warning"
                        isDuration={true}
                    />
                </Grid>
                <Grid item xs={12} sm={6} md={2.4}>
                    <MetricCard
                        title="AVG. PARTICIPANTS / MEETING"
                        value={current.avg_participants}
                        prevValue={previous.avg_participants}
                        periodLabel={period_label}
                        icon={<GroupsIcon fontSize="small" />}
                        color="secondary"
                    />
                </Grid>
                <Grid item xs={12} sm={6} md={2.4}>
                    <MetricCard
                        title="AVG. MEETING DURATION"
                        value={current.avg_duration}
                        prevValue={previous.avg_duration}
                        periodLabel={period_label}
                        icon={<TimerIcon fontSize="small" />}
                        color="info"
                        isDuration={true}
                    />
                </Grid>
            </Grid>
            <ZoomMeetingAdvancedCharts analytics={analytics} />
        </Box>
    );
};

export default ZoomMeetingAnalytics;
