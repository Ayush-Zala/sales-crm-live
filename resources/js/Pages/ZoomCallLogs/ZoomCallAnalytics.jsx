import React from "react";
import { Grid, Card, Typography, Box, alpha, useTheme } from "@mui/material";
import PhoneInTalkIcon from "@mui/icons-material/PhoneInTalk";
import PhoneCallbackIcon from "@mui/icons-material/PhoneCallback";
import PhoneMissedIcon from "@mui/icons-material/PhoneMissed";
import AssessmentIcon from "@mui/icons-material/Assessment";
import AccessTimeIcon from "@mui/icons-material/AccessTime";
import HourglassEmptyIcon from "@mui/icons-material/HourglassEmpty";
import TrendingUpIcon from "@mui/icons-material/TrendingUp";
import TrendingDownIcon from "@mui/icons-material/TrendingDown";
import DragHandleIcon from "@mui/icons-material/DragHandle";

const formatDuration = (seconds) => {
    if (!seconds) return "0s";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return m > 0 ? `${m}m ${s}s` : `${s}s`;
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
    
    // For durations, we might want to show difference in seconds instead of %
    const diffDisplay = isDuration 
        ? (isSame ? "0s" : formatDuration(Math.abs(value - prevValue)))
        : diffFormatted;

    // Is it a positive trend? Usually more calls, more answered, higher rate is good.
    // For missed calls and wait time, lower is better.
    let isPositiveTrend = isUp;
    if (title === "Missed Calls" || title === "Avg Wait Time") {
        isPositiveTrend = isDown;
    }

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
                <Typography variant="h4" fontWeight={700} sx={{ mb: 1, color: "text.primary" }}>
                    {isDuration ? formatDuration(value) : (title === "Answer Rate" ? `${value}%` : value.toLocaleString())}
                </Typography>
                <Box display="flex" alignItems="center" gap={0.5} flexWrap="wrap">
                    {!isSame && (
                        <>
                            {isUp && <TrendingUpIcon sx={{ fontSize: 16, color: trendColor }} />}
                            {isDown && <TrendingDownIcon sx={{ fontSize: 16, color: trendColor }} />}
                            <Typography variant="caption" sx={{ color: trendColor, fontWeight: 600, whiteSpace: "nowrap" }}>
                                {(isUp ? "+" : "-")}{diffDisplay}
                            </Typography>
                        </>
                    )}
                    <Typography variant="caption" color="text.secondary" sx={{ whiteSpace: "nowrap" }}>
                        vs {periodLabel}
                    </Typography>
                </Box>
            </Box>
        </Card>
    );
};

const ZoomCallAnalytics = ({ analytics }) => {
    if (!analytics) return null;

    const { current, previous, period_label } = analytics;

    return (
        <Grid container spacing={2} sx={{ mb: 3 }}>
            <Grid item xs={12} sm={6} md={4} lg={2}>
                <MetricCard
                    title="Total Calls"
                    value={current.total_calls}
                    prevValue={previous.total_calls}
                    periodLabel={period_label}
                    icon={<PhoneInTalkIcon />}
                    color="primary"
                />
            </Grid>
            <Grid item xs={12} sm={6} md={4} lg={2}>
                <MetricCard
                    title="Answered Calls"
                    value={current.answered_calls}
                    prevValue={previous.answered_calls}
                    periodLabel={period_label}
                    icon={<PhoneCallbackIcon />}
                    color="success"
                />
            </Grid>
            <Grid item xs={12} sm={6} md={4} lg={2}>
                <MetricCard
                    title="Missed Calls"
                    value={current.missed_calls}
                    prevValue={previous.missed_calls}
                    periodLabel={period_label}
                    icon={<PhoneMissedIcon />}
                    color="error"
                />
            </Grid>
            <Grid item xs={12} sm={6} md={4} lg={2}>
                <MetricCard
                    title="Answer Rate"
                    value={current.answer_rate}
                    prevValue={previous.answer_rate}
                    periodLabel={period_label}
                    icon={<AssessmentIcon />}
                    color="secondary"
                />
            </Grid>
            <Grid item xs={12} sm={6} md={4} lg={2}>
                <MetricCard
                    title="Avg Call Duration"
                    value={current.avg_call_duration}
                    prevValue={previous.avg_call_duration}
                    periodLabel={period_label}
                    icon={<AccessTimeIcon />}
                    color="warning"
                    isDuration={true}
                />
            </Grid>
            <Grid item xs={12} sm={6} md={4} lg={2}>
                <MetricCard
                    title="Avg Wait Time"
                    value={current.avg_wait_time}
                    prevValue={previous.avg_wait_time}
                    periodLabel={period_label}
                    icon={<HourglassEmptyIcon />}
                    color="error"
                    isDuration={true}
                />
            </Grid>
        </Grid>
    );
};

export default ZoomCallAnalytics;
