import React from "react";
import { Grid, Card, Typography, Box, useTheme } from "@mui/material";
import { LineChart } from "@mui/x-charts/LineChart";
import { PieChart } from "@mui/x-charts/PieChart";

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
                <Typography variant="h6" fontWeight={700} fontSize={16}>
                    {title}
                </Typography>
                {rightAction}
            </Box>
            <Box sx={{ flexGrow: 1, position: "relative", minHeight: 250, width: "100%" }}>
                {children}
            </Box>
        </Card>
    );
};

const CustomDonutChart = ({ data, total }) => {
    return (
        <Box display="flex" alignItems="center" height="100%">
            <Box position="relative" display="flex" justifyContent="center" alignItems="center" sx={{ width: 180, height: 200 }}>
                <PieChart
                    series={[
                        {
                            data: data,
                            innerRadius: 65,
                            outerRadius: 90,
                            paddingAngle: 2,
                            cornerRadius: 2,
                            highlightScope: { faded: "global", highlighted: "item" },
                        },
                    ]}
                    height={200}
                    width={180}
                    margin={{ right: 0, bottom: 0, left: 0, top: 0 }}
                    slotProps={{ legend: { hidden: true } }}
                />
                <Box position="absolute" display="flex" flexDirection="column" alignItems="center" sx={{ pointerEvents: "none" }}>
                    <Typography variant="h5" fontWeight={700} color="text.primary" sx={{ lineHeight: 1.2 }}>
                        {total.toLocaleString()}
                    </Typography>
                    <Typography variant="caption" color="text.secondary" fontWeight={500}>
                        Total Calls
                    </Typography>
                </Box>
            </Box>
            <Box flex={1} display="flex" flexDirection="column" gap={2} ml={1}>
                {data.map((item, idx) => {
                    const percentage = total > 0 ? ((item.value / total) * 100).toFixed(1) : 0;
                    return (
                        <Box key={idx} display="flex" gap={1.5} alignItems="flex-start">
                            <Box width={14} height={14} bgcolor={item.color} borderRadius={0.5} mt={0.4} flexShrink={0} />
                            <Box>
                                <Typography variant="body2" fontWeight={700} color="text.primary" sx={{ lineHeight: 1.2 }}>
                                    {item.label}
                                </Typography>
                                <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 0.2 }}>
                                    {item.value.toLocaleString()} ({percentage}%)
                                </Typography>
                            </Box>
                        </Box>
                    );
                })}
            </Box>
        </Box>
    );
};

const ZoomCallCharts = ({ analytics }) => {
    const theme = useTheme();
    if (!analytics || !analytics.charts) return null;

    const { current, charts } = analytics;
    const volumeData = charts.volume_over_time || [];
    const inboundOutbound = charts.inbound_outbound || { inbound: 0, outbound: 0 };

    // Format data for LineChart
    const xLabels = volumeData.map((d) => {
        const date = new Date(d.date);
        return date.toLocaleDateString("en-US", { month: "short", day: "numeric" });
    });

    const seriesData = [
        {
            data: volumeData.map((d) => d.total),
            label: "Total Calls",
            color: theme.palette.primary.main,
            curve: "linear",
            showMark: true,
        },
        {
            data: volumeData.map((d) => d.inbound),
            label: "Inbound",
            color: theme.palette.success.main,
            curve: "linear",
            showMark: true,
        },
        {
            data: volumeData.map((d) => d.outbound),
            label: "Outbound",
            color: theme.palette.secondary.main,
            curve: "linear",
            showMark: true,
        },
    ];

    // Format data for PieCharts
    const totalOutcome = current.answered_calls + current.missed_calls;
    const outcomeData = [
        { id: 0, value: current.answered_calls, label: "Answered", color: theme.palette.success.main },
        { id: 1, value: current.missed_calls, label: "Missed", color: theme.palette.error.main },
    ];

    const totalIO = inboundOutbound.inbound + inboundOutbound.outbound;
    const ioData = [
        { id: 0, value: inboundOutbound.inbound, label: "Inbound", color: theme.palette.primary.main },
        { id: 1, value: inboundOutbound.outbound, label: "Outbound", color: theme.palette.secondary.main },
    ];

    return (
        <Grid container spacing={2} sx={{ mb: 3 }}>
            {/* Line Chart */}
            <Grid item xs={12} lg={6}>
                <ChartCard 
                    title="Call Volume Over Time"
                    rightAction={
                        <Box display="flex" gap={2} alignItems="center">
                            <Box display="flex" alignItems="center" gap={0.5}>
                                <Box width={12} height={12} bgcolor={theme.palette.primary.main} borderRadius={0.5} />
                                <Typography variant="caption" color="text.secondary" fontWeight={500}>Total Calls</Typography>
                            </Box>
                            <Box display="flex" alignItems="center" gap={0.5}>
                                <Box width={12} height={12} bgcolor={theme.palette.success.main} borderRadius={0.5} />
                                <Typography variant="caption" color="text.secondary" fontWeight={500}>Inbound</Typography>
                            </Box>
                            <Box display="flex" alignItems="center" gap={0.5}>
                                <Box width={12} height={12} bgcolor={theme.palette.secondary.main} borderRadius={0.5} />
                                <Typography variant="caption" color="text.secondary" fontWeight={500}>Outbound</Typography>
                            </Box>
                        </Box>
                    }
                >
                    {volumeData.length > 0 ? (
                        <LineChart
                            xAxis={[{ scaleType: "point", data: xLabels }]}
                            series={seriesData}
                            height={300}
                            margin={{ top: 10, bottom: 30, left: 40, right: 20 }}
                            slotProps={{
                                legend: { hidden: true },
                            }}
                        />
                    ) : (
                        <Box display="flex" justifyContent="center" alignItems="center" height="100%">
                            <Typography color="text.secondary">No data available for this period</Typography>
                        </Box>
                    )}
                </ChartCard>
            </Grid>

            {/* Outcome Pie Chart */}
            <Grid item xs={12} md={6} lg={3}>
                <ChartCard title="Call Outcome">
                    {totalOutcome > 0 ? (
                        <CustomDonutChart data={outcomeData} total={totalOutcome} />
                    ) : (
                        <Box display="flex" justifyContent="center" alignItems="center" height="100%">
                            <Typography color="text.secondary">No data</Typography>
                        </Box>
                    )}
                </ChartCard>
            </Grid>

            {/* Inbound vs Outbound Pie Chart */}
            <Grid item xs={12} md={6} lg={3}>
                <ChartCard title="Inbound vs Outbound">
                    {totalIO > 0 ? (
                        <CustomDonutChart data={ioData} total={totalIO} />
                    ) : (
                        <Box display="flex" justifyContent="center" alignItems="center" height="100%">
                            <Typography color="text.secondary">No data</Typography>
                        </Box>
                    )}
                </ChartCard>
            </Grid>
        </Grid>
    );
};

export default ZoomCallCharts;
