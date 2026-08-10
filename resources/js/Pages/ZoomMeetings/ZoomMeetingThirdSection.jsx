import React from "react";
import { Grid, Card, Typography, Box, useTheme, Button } from "@mui/material";
import { LineChart } from "@mui/x-charts/LineChart";
import { BarChart } from "@mui/x-charts/BarChart";
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

const FilterDropdown = ({ label }) => (
    <Button
        variant="outlined"
        size="small"
        endIcon={<KeyboardArrowDownIcon />}
        sx={{
            textTransform: 'none',
            color: 'text.secondary',
            borderColor: 'divider',
            borderRadius: 2,
            py: 0.5,
            px: 1.5,
            fontSize: 12
        }}
    >
        {label}
    </Button>
);

const ZoomMeetingThirdSection = ({ analytics }) => {
    const theme = useTheme();
    if (!analytics || !analytics.charts) return null;

    const { charts } = analytics;
    
    // 1. Participation Trend
    const meetingsOverTime = charts.meetings_over_time || [];
    const dates = meetingsOverTime.map(d => {
        const dateObj = new Date(d.date);
        return dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    // Avg participants per meeting, default to 0 if no meetings
    const participationData = meetingsOverTime.map(d => 
        d.total_meetings > 0 ? Number((d.total_participants / d.total_meetings).toFixed(1)) : 0
    );

    // 2. Meetings by Time of Day
    const hourlyDistribution = charts.hourly_distribution || [];
    const hours = hourlyDistribution.map(d => {
        const h = d.hour;
        if (h === 0) return '12 AM';
        if (h === 12) return '12 PM';
        return h < 12 ? `${h} AM` : `${h - 12} PM`;
    });
    // For cleaner x-axis labels, only show specific intervals like the screenshot
    const hourlyData = hourlyDistribution.map(d => d.total);

    // 3. Meeting Duration Distribution
    const durationDist = charts.duration_distribution || [];
    const totalMeetingsForDuration = durationDist.reduce((acc, curr) => acc + curr.total, 0);

    return (
        <Grid container spacing={2} sx={{ mb: 3 }}>
            {/* 1. Participation Trend (Avg.) */}
            <Grid item xs={12} md={4}>
                <ChartCard 
                    title="Participation Trend (Avg.)"
                >
                    {meetingsOverTime.length > 0 ? (
                        <LineChart
                            xAxis={[{ scaleType: 'point', data: dates, tickLabelStyle: { fontSize: 10 } }]}
                            yAxis={[{ min: 0 }]}
                            series={[{ data: participationData, color: theme.palette.primary.main, area: true }]}
                            height={220}
                            margin={{ top: 10, bottom: 25, left: 30, right: 10 }}
                            grid={{ vertical: true }}
                            sx={{
                                '.MuiAreaElement-root': { fillOpacity: 0.1 },
                                '.MuiChartsAxis-line': { stroke: 'none' },
                                '.MuiChartsAxis-tick': { stroke: 'none' }
                            }}
                        />
                    ) : (
                        <Box display="flex" justifyContent="center" alignItems="center" flex={1}>
                            <Typography color="text.secondary">No data</Typography>
                        </Box>
                    )}
                </ChartCard>
            </Grid>

            {/* 2. Meetings by Time of Day */}
            <Grid item xs={12} md={4}>
                <ChartCard 
                    title="Meetings by Time of Day"
                >
                    {hourlyData.some(v => v > 0) ? (
                        <BarChart
                            xAxis={[{ 
                                scaleType: 'band', 
                                data: hours,
                                tickLabelStyle: { fontSize: 10 },
                            }]}
                            yAxis={[{ min: 0 }]}
                            series={[{ data: hourlyData, color: theme.palette.primary.main }]}
                            height={220}
                            margin={{ top: 10, bottom: 25, left: 30, right: 10 }}
                            sx={{
                                '.MuiChartsAxis-line': { stroke: 'none' },
                                '.MuiChartsAxis-tick': { stroke: 'none' },
                                // Show only some x-axis labels to avoid clutter
                                '& .MuiChartsAxis-bottom .MuiChartsAxis-tickLabel:not(:nth-of-type(4n+1))': {
                                    display: 'none'
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

            {/* 3. Meeting Duration Distribution */}
            <Grid item xs={12} md={4}>
                <ChartCard 
                    title="Meeting Duration Distribution"
                >
                    {durationDist.length > 0 ? (
                        <Box display="flex" flexDirection="column" gap={2} mt={1}>
                            {durationDist.map((item, index) => {
                                const percentage = totalMeetingsForDuration > 0 
                                    ? ((item.total / totalMeetingsForDuration) * 100).toFixed(1)
                                    : 0;

                                return (
                                    <Box key={index} display="flex" alignItems="center" justifyContent="space-between">
                                        <Typography variant="body2" sx={{ width: '80px', color: 'text.secondary', fontSize: '0.8rem' }}>
                                            {item.bucket}
                                        </Typography>
                                        <Box sx={{ flexGrow: 1, mx: 2, height: 8, bgcolor: 'grey.100', borderRadius: 4, overflow: 'hidden' }}>
                                            <Box 
                                                sx={{ 
                                                    width: `${percentage}%`, 
                                                    height: '100%', 
                                                    bgcolor: theme.palette.primary.main,
                                                    borderRadius: 4
                                                }} 
                                            />
                                        </Box>
                                        <Typography variant="body2" sx={{ fontWeight: 600, minWidth: '60px', textAlign: 'right', fontSize: '0.8rem' }}>
                                            {item.total} <span style={{ color: '#888', fontWeight: 'normal' }}>({percentage}%)</span>
                                        </Typography>
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

export default ZoomMeetingThirdSection;
