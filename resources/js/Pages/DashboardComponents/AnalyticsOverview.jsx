import React, { useState } from 'react';
import { Box, Card, Typography, Select, MenuItem, Stack, Grid, Dialog, DialogTitle, DialogContent, DialogActions, Button } from '@mui/material';
import { LineChart } from '@mui/x-charts/LineChart';
import { Video, Phone, TrendingUp, DollarSign, Activity } from 'lucide-react';
import useUpdateSearchParam from "@/hooks/use-update-search-params";
import { DateCalendar } from "@mui/x-date-pickers";
import { formatDate } from "@/utils/date-time-formatters";
import ZoomCallAnalytics from "../ZoomCallLogs/ZoomCallAnalytics";
import ZoomCallCharts from "../ZoomCallLogs/ZoomCallCharts";
import ZoomMeetingAnalytics from "../ZoomMeetings/ZoomMeetingAnalytics";

export default function AnalyticsOverview({ data = null, zoomAnalytics = null, zoomMeetingAnalytics = null }) {
    const [openDialog, setOpenDialog] = useState(false);
    const [dateRange, setDateRange] = useState([null, null]);

    // Parse the current filter from the URL so the select box stays in sync
    const queryParams = new URLSearchParams(window.location.search);
    const currentFilter = queryParams.get('analytics_filter') || 'this_month';

    const handleFilterChange = (event) => {
        const val = event.target.value;
        if (val === 'custom') {
            setOpenDialog(true);
        } else {
            useUpdateSearchParam({ analytics_filter: val }, "/dashboard", { only: ['analyticsOverview', 'zoomAnalytics', 'zoomMeetingAnalytics'] });
        }
    };
    if (!data || !data.dailyData || data.dailyData.length === 0) return null;

    const dailyData = data.dailyData;
    const trends = data.trends || {};

    const dates = dailyData.map(d => d.date);
    const zoomCalls = dailyData.map(d => d.zoom_calls);
    const crmCalls = dailyData.map(d => d.crm_calls);
    const totalCalls = dailyData.map(d => d.total_calls);

    const totalZoom = zoomCalls.reduce((a, b) => a + b, 0);
    const totalCrm = crmCalls.reduce((a, b) => a + b, 0);
    const totalAll = totalCalls.reduce((a, b) => a + b, 0);
    const totalSales = dailyData.reduce((a, b) => a + b.sales, 0);

    const formatTrend = (trendValue) => {
        if (trendValue === null || trendValue === undefined) return null;
        return trendValue >= 0 ? `↑ ${trendValue}%` : `↓ ${Math.abs(trendValue)}%`;
    };

    const getTrendLabel = () => {
        const labels = {
            'today': 'vs yesterday',
            'yesterday': 'vs previous day',
            'last_week': 'vs previous week',
            'this_month': 'vs last month',
            'last_month': 'vs previous month',
            'this_year': 'vs last year',
            'custom': 'vs previous period',
            'life_time': ''
        };
        return labels[currentFilter] || 'vs previous';
    };

    const getTrendColor = (trendValue) => (trendValue >= 0 ? 'green' : 'red');

    return (
        <Card sx={{ p: 3, borderRadius: 4, boxShadow: '0 10px 30px -10px rgba(0,0,0,0.1)' }}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    <Activity size={24} color="#1976d2" />
                    <Typography variant="h6" fontWeight="bold">Analytics Overview</Typography>
                </Box>

                <Box sx={{ display: { xs: 'none', md: 'flex' }, alignItems: 'center', gap: 3 }}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <Box sx={{ width: 12, height: 12, bgcolor: '#1976d2', borderRadius: 0.5 }} />
                        <Typography variant="body2">Total Calls</Typography>
                    </Box>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <Box sx={{ width: 12, height: 12, bgcolor: '#4caf50', borderRadius: 0.5 }} />
                        <Typography variant="body2">Zoom Calls</Typography>
                    </Box>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <Box sx={{ width: 12, height: 12, bgcolor: '#ff9800', borderRadius: 0.5 }} />
                        <Typography variant="body2">CRM Calls</Typography>
                    </Box>
                </Box>

                <Select size="small" value={currentFilter} onChange={handleFilterChange} sx={{ borderRadius: 2 }}>
                    <MenuItem value="today">Today</MenuItem>
                    <MenuItem value="yesterday">Yesterday</MenuItem>
                    <MenuItem value="last_week">Last Week</MenuItem>
                    <MenuItem value="this_month">This Month</MenuItem>
                    <MenuItem value="life_time">Life Time</MenuItem>
                    <MenuItem value="custom">Custom</MenuItem>
                </Select>
            </Box>

            <Grid container spacing={4}>
                {/* Stats Sidebar */}
                <Grid item xs={12} md={3}>
                    <Stack spacing={2}>
                        <StatBox
                            icon={<Activity size={20} />}
                            title="Total Logged Calls"
                            value={totalAll.toLocaleString()}
                            trend={formatTrend(trends.total)}
                            trendColor={getTrendColor(trends.total)}
                            trendLabel={getTrendLabel()}
                            color="#e3f2fd"
                        />
                        <StatBox
                            icon={<Video size={20} />}
                            title="Zoom Calls"
                            value={totalZoom.toLocaleString()}
                            trend={formatTrend(trends.zoom)}
                            trendColor={getTrendColor(trends.zoom)}
                            trendLabel={getTrendLabel()}
                            color="#e8f5e9"
                        />
                        <StatBox
                            icon={<Phone size={20} />}
                            title="CRM Direct Calls"
                            value={totalCrm.toLocaleString()}
                            trend={formatTrend(trends.crm)}
                            trendColor={getTrendColor(trends.crm)}
                            trendLabel={getTrendLabel()}
                            color="#fff3e0"
                        />
                        <StatBox
                            icon={<DollarSign size={20} />}
                            title="Sales Made"
                            value={totalSales.toLocaleString()}
                            trend={formatTrend(trends.sales)}
                            trendColor={getTrendColor(trends.sales)}
                            trendLabel={getTrendLabel()}
                            color="#f3e5f5"
                        />
                    </Stack>
                </Grid>

                {/* Chart Area */}
                <Grid item xs={12} md={9}>
                    <Box sx={{ width: '100%', height: 350 }}>
                        <LineChart
                            series={[
                                { data: totalCalls, label: 'Total Calls', color: '#1976d2', showMark: false, curve: 'linear' },
                                { data: zoomCalls, label: 'Zoom Calls', color: '#4caf50', showMark: false, curve: 'linear' },
                                { data: crmCalls, label: 'CRM Calls', color: '#ff9800', showMark: false, curve: 'linear' },
                            ]}
                            xAxis={[{ scaleType: 'point', data: dates }]}
                            margin={{ top: 20, bottom: 30, left: 40, right: 20 }}
                            slotProps={{
                                legend: {
                                    hidden: true
                                }
                            }}
                        />
                    </Box>
                </Grid>
            </Grid>

            {zoomAnalytics && (
                <Box sx={{ mt: 4, pt: 4, borderTop: '1px solid #eee' }}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 3 }}>
                        <Phone size={24} color="#ff9800" />
                        <Typography variant="h6" fontWeight="bold">Zoom Phone Call Logs Analytics</Typography>
                    </Box>
                    <ZoomCallAnalytics analytics={zoomAnalytics} />
                    <ZoomCallCharts analytics={zoomAnalytics} />
                </Box>
            )}

            {zoomMeetingAnalytics && (
                <Box sx={{ mt: 4, pt: 4, borderTop: '1px solid #eee' }}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 3 }}>
                        <Video size={24} color="#1976d2" />
                        <Typography variant="h6" fontWeight="bold">Zoom Meeting Logs Analytics</Typography>
                    </Box>
                    <ZoomMeetingAnalytics analytics={zoomMeetingAnalytics} />
                </Box>
            )}

            <CustomDateRangeDialog
                open={openDialog}
                onClose={() => setOpenDialog(false)}
                dateRange={dateRange}
                setDateRange={setDateRange}
            />
        </Card>
    );
}

const CustomDateRangeDialog = ({ open, onClose, dateRange, setDateRange }) => {
    const handleDateChange = (index, newDate) => {
        const updatedDateRange = [...dateRange];
        updatedDateRange[index] = newDate;
        setDateRange(updatedDateRange);
    };

    const handleApply = () => {
        if (!dateRange[0] || !dateRange[1]) return;
        useUpdateSearchParam({
            analytics_filter: 'custom',
            analytics_start: formatDate(dateRange[0].toISOString(), "yyyy-MM-dd"),
            analytics_end: formatDate(dateRange[1].toISOString(), "yyyy-MM-dd")
        }, "/dashboard", { only: ['analyticsOverview', 'zoomAnalytics', 'zoomMeetingAnalytics'] });
        onClose();
    };

    return (
        <Dialog
            open={open}
            onClose={(_, reason) => reason !== "backdropClick" && onClose()}
            maxWidth="md"
            fullWidth
        >
            <DialogTitle>Select Date Range</DialogTitle>
            <DialogContent dividers>
                <Grid container spacing={2} alignItems="flex-start">
                    <Grid item xs={6}>
                        <Typography variant="body1" fontWeight="bold">
                            Start Date:
                        </Typography>
                        <DateCalendar
                            value={dateRange[0]}
                            onChange={(newDate) => handleDateChange(0, newDate)}
                        />
                    </Grid>
                    <Grid item xs={6}>
                        <Typography variant="body1" fontWeight="bold">
                            End Date:
                        </Typography>
                        <DateCalendar
                            value={dateRange[1]}
                            onChange={(newDate) => handleDateChange(1, newDate)}
                        />
                    </Grid>
                </Grid>
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose} color="error">
                    Cancel
                </Button>
                <Button onClick={handleApply} color="primary">
                    Apply
                </Button>
            </DialogActions>
        </Dialog>
    );
};

function StatBox({ icon, title, value, trend, color, trendColor, trendLabel }) {
    return (
        <Box sx={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            p: 2,
            borderRadius: 3,
            border: '1px solid #eee',
            bgcolor: 'white'
        }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                <Box sx={{
                    bgcolor: color,
                    p: 1.5,
                    borderRadius: 2,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                }}>
                    {icon}
                </Box>
                <Box>
                    <Typography variant="body2" color="text.secondary">{title}</Typography>
                    <Typography variant="h6" fontWeight="bold">{value}</Typography>
                </Box>
            </Box>
            <Box sx={{ minHeight: 40, textAlign: 'right' }}>
                {trend !== null ? (
                    <>
                        <Typography variant="caption" sx={{ color: trendColor === 'green' ? 'success.main' : 'error.main', fontWeight: 'bold' }}>
                            {trend}
                        </Typography>
                        <Typography variant="caption" display="block" color="text.secondary">
                            {trendLabel || 'vs previous'}
                        </Typography>
                    </>
                ) : null}
            </Box>
        </Box>
    );
}
