import React from "react";
import { Grid, Card, Typography, Box, useTheme, Button } from "@mui/material";
import { BarChart } from "@mui/x-charts/BarChart";
import { LineChart } from "@mui/x-charts/LineChart";
import { PieChart } from "@mui/x-charts/PieChart";
import KeyboardArrowDownIcon from '@mui/icons-material/KeyboardArrowDown';

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

const ZoomMeetingAdvancedCharts = ({ analytics }) => {
    const theme = useTheme();
    if (!analytics || !analytics.charts) return null;

    const { charts } = analytics;
    
    // 1. Meetings Over Time
    const meetingsOverTime = charts.meetings_over_time || [];
    const dates = meetingsOverTime.map(d => {
        const dateObj = new Date(d.date);
        return dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    const meetingsData = meetingsOverTime.map(d => d.total_meetings);

    // 2. Meeting Type Distribution
    const typeDist = charts.meeting_type_distribution || [];
    const pieData = typeDist.map((item, index) => ({
        id: index,
        value: item.total,
        label: item.label,
    }));
    const totalMeetings = typeDist.reduce((acc, curr) => acc + curr.total, 0);

    const pieColors = ['#1976d2', '#4caf50', '#ff9800', '#9c27b0', '#f44336', '#00bcd4'];

    // 3. Top Hosts
    const topHosts = charts.top_hosts || [];

    return (
        <Grid container spacing={2} sx={{ mb: 3 }}>
            {/* 1. Meetings Over Time */}
            <Grid item xs={12} md={4}>
                <ChartCard title="Meetings Over Time">
                    {meetingsOverTime.length > 0 ? (
                        <LineChart
                            xAxis={[{ scaleType: 'point', data: dates, tickLabelStyle: { fontSize: 10 } }]}
                            yAxis={[{ min: 0 }]}
                            series={[{ data: meetingsData, color: theme.palette.primary.main, area: true }]}
                            height={220}
                            margin={{ top: 10, bottom: 25, left: 30, right: 25 }}
                            grid={{ vertical: true }}
                            sx={{
                                '.MuiAreaElement-root': {
                                    fillOpacity: 0.1,
                                },
                                '.MuiChartsAxis-line': {
                                    stroke: 'none'
                                },
                                '.MuiChartsAxis-tick': {
                                    stroke: 'none'
                                }
                            }}
                        />
                    ) : (
                        <Box display="flex" justifyContent="center" alignItems="center" flex={1}>
                            <Typography color="text.secondary">No data</Typography>
                        </Box>
                    )}
                </ChartCard>
            </Grid>

            {/* 2. Meeting Type Distribution */}
            <Grid item xs={12} md={4}>
                <ChartCard title="Meeting Type Distribution">
                    {pieData.length > 0 ? (
                        <Box display="flex" alignItems="center" justifyContent="center" flex={1}>
                            <Box position="relative" width={200} height={200} display="flex" justifyContent="center" alignItems="center">
                                <PieChart
                                    colors={pieColors}
                                    series={[
                                        {
                                            data: pieData,
                                            innerRadius: 60,
                                            outerRadius: 80,
                                            paddingAngle: 2,
                                            cornerRadius: 4,
                                            cx: 95,
                                            cy: 95,
                                        }
                                    ]}
                                    height={200}
                                    width={200}
                                    margin={{ right: 0, left: 0, top: 0, bottom: 0 }}
                                    slotProps={{
                                        legend: { hidden: true }
                                    }}
                                >
                                    <g transform={`translate(95, 95)`}>
                                        <text textAnchor="middle" dominantBaseline="central" style={{ fontSize: 24, fontWeight: 'bold' }}>
                                            {totalMeetings}
                                        </text>
                                        <text textAnchor="middle" dominantBaseline="central" y={20} style={{ fontSize: 12, fill: theme.palette.text.secondary }}>
                                            Meetings
                                        </text>
                                    </g>
                                </PieChart>
                            </Box>
                            
                            {/* Custom Legend */}
                            <Box display="flex" flexDirection="column" gap={1.5} ml={2} flex={1}>
                                {pieData.map((item, idx) => {
                                    const color = pieColors[idx % pieColors.length];
                                    const pct = totalMeetings > 0 ? ((item.value / totalMeetings) * 100).toFixed(1) : 0;
                                    
                                    return (
                                        <Box key={idx} display="flex" alignItems="flex-start" gap={1}>
                                            <Box sx={{ width: 12, height: 12, borderRadius: 0.5, bgcolor: color, mt: 0.5, flexShrink: 0 }} />
                                            <Box display="flex" flexDirection="column">
                                                <Typography variant="caption" sx={{ fontWeight: 600, color: 'text.primary', lineHeight: 1.2 }}>
                                                    {item.label}
                                                </Typography>
                                                <Typography variant="caption" sx={{ color: 'text.secondary', lineHeight: 1.2, mt: 0.5 }}>
                                                    {item.value} ({pct}%)
                                                </Typography>
                                            </Box>
                                        </Box>
                                    );
                                })}
                            </Box>
                        </Box>
                    ) : (
                        <Box display="flex" justifyContent="center" alignItems="center" flex={1}>
                            <Typography color="text.secondary">No data</Typography>
                        </Box>
                    )}
                </ChartCard>
            </Grid>

            {/* 3. Top Hosts */}
            <Grid item xs={12} md={4}>
                <ChartCard title="Meetings by Host (Top 5)">
                    {topHosts.length > 0 ? (
                        <Box display="flex" flexDirection="column" gap={2} mt={1} flex={1} justifyContent="center">
                            {topHosts.map((user, idx) => {
                                const maxCalls = topHosts[0].total_meetings;
                                const widthPct = Math.max((user.total_meetings / maxCalls) * 100, 5);
                                const pctOfTotal = totalMeetings > 0 ? ((user.total_meetings / totalMeetings) * 100).toFixed(1) : 0;
                                return (
                                    <Box key={idx} display="flex" alignItems="center">
                                        <Typography variant="caption" sx={{ width: 100, whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis", mr: 1, fontWeight: 500 }}>
                                            {user.host_name}
                                        </Typography>
                                        <Box flex={1} display="flex" alignItems="center">
                                            <Box sx={{ width: `${widthPct}%`, bgcolor: theme.palette.primary.main, height: 12, borderRadius: 1 }} />
                                            <Typography variant="caption" sx={{ ml: 1, color: "text.primary", fontWeight: 600, minWidth: 20 }}>
                                                {user.total_meetings}
                                            </Typography>
                                            <Typography variant="caption" sx={{ ml: 1, color: "text.secondary" }}>
                                                ({pctOfTotal}%)
                                            </Typography>
                                        </Box>
                                    </Box>
                                );
                            })}
                        </Box>
                    ) : (
                        <Box display="flex" justifyContent="center" alignItems="center" flex={1}>
                            <Typography color="text.secondary">No data</Typography>
                        </Box>
                    )}
                </ChartCard>
            </Grid>
        </Grid>
    );
};

export default ZoomMeetingAdvancedCharts;
