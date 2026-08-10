import React from "react";
import { Grid, Card, Typography, Box, useTheme, Button } from "@mui/material";
import { BarChart } from "@mui/x-charts/BarChart";
import KeyboardArrowDownIcon from '@mui/icons-material/KeyboardArrowDown';
import ArrowDownwardIcon from '@mui/icons-material/ArrowDownward';
import ArrowUpwardIcon from '@mui/icons-material/ArrowUpward';
import DragHandleIcon from '@mui/icons-material/DragHandle';

const formatDuration = (seconds) => {
    if (!seconds) return "0s";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return m > 0 ? `${m}m ${s}s` : `${s}s`;
};

const ChartCard = ({ title, children, rightAction }) => {
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
                display: "flex",
                flexDirection: "column",
            }}
        >
            <Box display="flex" justifyContent="space-between" alignItems="center" mb={2}>
                <Typography variant="h6" fontWeight={700} fontSize={15}>
                    {title}
                </Typography>
                {rightAction}
            </Box>
            <Box sx={{ flexGrow: 1, position: "relative", minHeight: 220, width: "100%", display: "flex", flexDirection: "column" }}>
                {children}
            </Box>
        </Card>
    );
};

const FilterDropdown = ({ label }) => (
    <Button 
        variant="outlined" 
        size="small" 
        endIcon={<KeyboardArrowDownIcon />}
        sx={{ 
            color: "text.secondary", 
            borderColor: "divider",
            textTransform: "none",
            borderRadius: 2,
            py: 0.2,
            px: 1,
            fontSize: 12
        }}
    >
        {label}
    </Button>
);

const ZoomCallAdvancedCharts = ({ analytics }) => {
    const theme = useTheme();
    if (!analytics || !analytics.charts) return null;

    const { charts, current, previous } = analytics;
    
    // 1. Top Users Data
    const topUsers = charts.top_users || [];
    
    // 2. Hourly Volume Data
    const hourlyDataRaw = charts.hourly_volume || [];
    const hourlyData = Array.from({ length: 24 }, (_, i) => {
        const found = hourlyDataRaw.find(d => parseInt(d.hour) === i);
        return {
            hour: i,
            label: i === 0 ? "12 AM" : (i < 12 ? `${i} AM` : (i === 12 ? "12 PM" : `${i-12} PM`)),
            total: found ? found.total : 0
        };
    });

    // 3. Average Wait Time Data
    const waitDist = charts.wait_time_distribution || {};
    const totalWaitCalls = parseInt(waitDist.bucket_0_5s||0) + parseInt(waitDist.bucket_5_10s||0) + 
                          parseInt(waitDist.bucket_10_20s||0) + parseInt(waitDist.bucket_20_30s||0) + 
                          parseInt(waitDist.bucket_30s_plus||0);
    
    const getWaitPct = (val) => totalWaitCalls > 0 ? Math.round((parseInt(val) / totalWaitCalls) * 100) : 0;
    const waitBuckets = [
        { label: "0-5s", pct: getWaitPct(waitDist.bucket_0_5s) },
        { label: "5-10s", pct: getWaitPct(waitDist.bucket_5_10s) },
        { label: "10-20s", pct: getWaitPct(waitDist.bucket_10_20s) },
        { label: "20-30s", pct: getWaitPct(waitDist.bucket_20_30s) },
        { label: "30s+", pct: getWaitPct(waitDist.bucket_30s_plus) },
    ];

    // Wait time trend
    const waitDiff = current.avg_wait_time - previous.avg_wait_time;
    const isWaitDown = waitDiff < 0; // Good
    const isWaitUp = waitDiff > 0; // Bad
    const isWaitSame = waitDiff === 0;

    // 4. Duration Distribution
    const durDist = charts.duration_distribution || {};
    const totalDurCalls = parseInt(durDist.bucket_0_30s||0) + parseInt(durDist.bucket_30s_1m||0) + 
                          parseInt(durDist.bucket_1m_3m||0) + parseInt(durDist.bucket_3m_5m||0) + 
                          parseInt(durDist.bucket_5m_10m||0) + parseInt(durDist.bucket_10m_plus||0);
    
    const getDurPct = (val) => totalDurCalls > 0 ? Math.round((parseInt(val) / totalDurCalls) * 100) : 0;
    const durBuckets = [
        { label: "0 - 30 sec", pct: getDurPct(durDist.bucket_0_30s) },
        { label: "30 sec - 1 min", pct: getDurPct(durDist.bucket_30s_1m) },
        { label: "1 - 3 min", pct: getDurPct(durDist.bucket_1m_3m) },
        { label: "3 - 5 min", pct: getDurPct(durDist.bucket_3m_5m) },
        { label: "5 - 10 min", pct: getDurPct(durDist.bucket_5m_10m) },
        { label: "10+ min", pct: getDurPct(durDist.bucket_10m_plus) },
    ];

    return (
        <Grid container spacing={2} sx={{ mb: 3 }}>
            {/* 1. Top Users */}
            <Grid item xs={12} md={6} lg={3}>
                <ChartCard title="Top Users by Total Calls">
                    {topUsers.length > 0 ? (
                        <Box display="flex" flexDirection="column" gap={1.5} mt={1}>
                            {topUsers.map((user, idx) => {
                                const maxCalls = topUsers[0].total;
                                const widthPct = Math.max((user.total / maxCalls) * 100, 5);
                                return (
                                    <Box key={idx} display="flex" alignItems="center">
                                        <Typography variant="caption" sx={{ width: 85, whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis", mr: 1 }}>
                                            {user.name}
                                        </Typography>
                                        <Box flex={1} display="flex" alignItems="center">
                                            <Box sx={{ width: `${widthPct}%`, bgcolor: theme.palette.primary.main, height: 12, borderRadius: 1 }} />
                                            <Typography variant="caption" sx={{ ml: 1, color: "text.secondary" }}>
                                                {user.total.toLocaleString()}
                                            </Typography>
                                        </Box>
                                    </Box>
                                );
                            })}
                            <Box display="flex" justifyContent="space-between" mt={1}>
                                <Typography variant="caption" color="text.secondary">0</Typography>
                                <Typography variant="caption" color="text.secondary">{Math.round(topUsers[0].total * 0.33).toLocaleString()}</Typography>
                                <Typography variant="caption" color="text.secondary">{Math.round(topUsers[0].total * 0.66).toLocaleString()}</Typography>
                                <Typography variant="caption" color="text.secondary">{topUsers[0].total.toLocaleString()}</Typography>
                            </Box>
                        </Box>
                    ) : (
                        <Box display="flex" justifyContent="center" alignItems="center" flex={1}>
                            <Typography color="text.secondary">No data</Typography>
                        </Box>
                    )}
                </ChartCard>
            </Grid>

            {/* 2. Hourly Volume */}
            <Grid item xs={12} md={6} lg={3}>
                <ChartCard title="Calls by Hour of Day">
                    <BarChart
                        xAxis={[{ scaleType: 'band', data: hourlyData.map(d => d.label), categoryGapRatio: 0.3, tickLabelStyle: { fontSize: 9 } }]}
                        series={[{ data: hourlyData.map(d => d.total), color: theme.palette.primary.main }]}
                        height={220}
                        margin={{ top: 10, bottom: 25, left: 30, right: 10 }}
                        slotProps={{ legend: { hidden: true } }}
                    />
                </ChartCard>
            </Grid>

            {/* 3. Average Wait Time */}
            <Grid item xs={12} md={6} lg={3}>
                <ChartCard title="Average Wait Time">
                    <Box display="flex" flexDirection="column" alignItems="center" justifyContent="center" flex={1} pb={2}>
                        <Typography variant="h3" fontWeight={700} color="text.primary">
                            {current.avg_wait_time}s
                        </Typography>
                        <Typography variant="body2" color="text.secondary" mt={0.5} mb={1}>
                            Avg Wait Time
                        </Typography>
                        <Box display="flex" alignItems="center" gap={0.5}>
                            {!isWaitSame && (
                                <>
                                    {isWaitDown && <ArrowDownwardIcon sx={{ fontSize: 16, color: "success.main" }} />}
                                    {isWaitUp && <ArrowUpwardIcon sx={{ fontSize: 16, color: "error.main" }} />}
                                    <Typography variant="caption" sx={{ color: isWaitDown ? "success.main" : "error.main", fontWeight: 600 }}>
                                        {Math.abs(waitDiff)}s
                                    </Typography>
                                </>
                            )}
                            <Typography variant="caption" color="text.secondary">
                                vs {analytics.period_label}
                            </Typography>
                        </Box>
                    </Box>
                    <Box display="flex" width="100%" height={40} borderRadius={1} overflow="hidden">
                        {waitBuckets.map((bucket, idx) => {
                            return (
                                <Box 
                                    key={idx} 
                                    sx={{ 
                                        width: `${Math.max(bucket.pct, 5)}%`, 
                                        bgcolor: `rgba(25, 118, 210, ${(idx + 3) * 0.15})`, 
                                        display: "flex", 
                                        flexDirection: "column",
                                        justifyContent: "center", 
                                        alignItems: "center",
                                        borderRight: idx < waitBuckets.length - 1 ? "1px solid rgba(255,255,255,0.3)" : "none",
                                        px: 0.5
                                    }}
                                >
                                    <Typography variant="caption" sx={{ fontSize: '0.65rem', fontWeight: 500, color: "white", lineHeight: 1.1, textAlign: 'center', whiteSpace: 'nowrap' }}>
                                        {bucket.label}
                                    </Typography>
                                    <Typography variant="caption" sx={{ fontSize: '0.65rem', fontWeight: 400, color: "white", lineHeight: 1.1, mt: 0.2 }}>
                                        {bucket.pct}%
                                    </Typography>
                                </Box>
                            );
                        })}
                    </Box>
                </ChartCard>
            </Grid>

            {/* 4. Duration Distribution */}
            <Grid item xs={12} md={6} lg={3}>
                <ChartCard title="Call Duration Distribution">
                    <Box display="flex" flexDirection="column" gap={1.5} mt={1}>
                        {durBuckets.map((bucket, idx) => {
                            return (
                                <Box key={idx} display="flex" alignItems="center">
                                    <Typography variant="caption" sx={{ width: 85, color: "text.secondary", mr: 1, textAlign: "right" }}>
                                        {bucket.label}
                                    </Typography>
                                    <Box flex={1} display="flex" alignItems="center" position="relative">
                                        <Box sx={{ width: `${bucket.pct}%`, bgcolor: theme.palette.primary.main, height: 10, borderRadius: 1 }} />
                                        <Typography variant="caption" sx={{ position: "absolute", right: 0, color: "text.secondary", fontSize: 10 }}>
                                            {bucket.pct}%
                                        </Typography>
                                    </Box>
                                </Box>
                            );
                        })}
                        <Box display="flex" justifyContent="space-between" mt={1} pl={10} pr={2}>
                            <Typography variant="caption" color="text.secondary">0%</Typography>
                            <Typography variant="caption" color="text.secondary">10%</Typography>
                            <Typography variant="caption" color="text.secondary">20%</Typography>
                            <Typography variant="caption" color="text.secondary">30%</Typography>
                        </Box>
                    </Box>
                </ChartCard>
            </Grid>
        </Grid>
    );
};

export default ZoomCallAdvancedCharts;
