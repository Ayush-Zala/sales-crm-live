import React, { useState, useEffect } from 'react';
import { Button, Menu, MenuItem, CircularProgress, Snackbar, Alert, Dialog, DialogTitle, DialogContent, DialogActions, TextField, Box } from '@mui/material';
import { Sync as SyncIcon } from '@mui/icons-material';
import { hasPermission } from '@/utils/AccessManager';
import { router } from '@inertiajs/react';

export default function ZoomSyncButton({ auth, type = "call_logs" }) {
    const [anchorEl, setAnchorEl] = useState(null);
    const [isSyncing, setIsSyncing] = useState(false);
    const [syncLogId, setSyncLogId] = useState(null);
    const [syncStatus, setSyncStatus] = useState(null);
    const [snackbarMsg, setSnackbarMsg] = useState({ open: false, message: "", severity: "success" });
    const [modalOpen, setModalOpen] = useState(false);
    const [selectedSyncType, setSelectedSyncType] = useState(null);
    
    // Default dates
    const today = new Date().toISOString().split('T')[0];
    const [startDate, setStartDate] = useState(today);
    const [endDate, setEndDate] = useState(today);

    const openMenu = Boolean(anchorEl);

    // Check permissions
    const canSyncCalls = hasPermission(auth, "Can Sync Zoom Call Logs");
    const canSyncMeetings = hasPermission(auth, "Can Sync Zoom Meetings");

    const canRender = type === 'meetings' ? canSyncMeetings : canSyncCalls;

    const handleSnackbarClose = () => setSnackbarMsg({ ...snackbarMsg, open: false });

    // Polling effect
    useEffect(() => {
        let interval;
        if (isSyncing && syncLogId) {
            interval = setInterval(() => {
                fetch(`/api/zoom-sync/${syncLogId}`)
                    .then(res => res.json())
                    .then(data => {
                        setSyncStatus(data.status);
                        if (data.status === 'completed' || data.status === 'failed') {
                            setIsSyncing(false);
                            setSyncLogId(null);
                            if (data.status === 'completed') {
                                setSnackbarMsg({ open: true, message: "Sync completed successfully!", severity: "success" });
                                setTimeout(() => {
                                    router.reload();
                                }, 1500);
                            } else {
                                setSnackbarMsg({ open: true, message: 'Sync failed: ' + data.error_message, severity: "error" });
                            }
                        }
                    })
                    .catch(err => console.error(err));
            }, 3000);
        }
        return () => clearInterval(interval);
    }, [isSyncing, syncLogId]);

    const handleClickMenu = (event) => {
        setAnchorEl(event.currentTarget);
    };

    const handleCloseMenu = () => {
        setAnchorEl(null);
    };

    const openSyncModal = (syncType) => {
        handleCloseMenu();
        setSelectedSyncType(syncType);
        setModalOpen(true);
    };

    const closeSyncModal = () => {
        setModalOpen(false);
        setSelectedSyncType(null);
    };

    const setQuickDate = (daysBack) => {
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - daysBack);
        
        setEndDate(end.toISOString().split('T')[0]);
        setStartDate(start.toISOString().split('T')[0]);
    };

    const triggerSync = () => {
        closeSyncModal();
        setIsSyncing(true);
        setSyncStatus('pending');

        fetch('/api/zoom-sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ 
                type: selectedSyncType,
                start_date: startDate,
                end_date: endDate
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.log_id) {
                setSyncLogId(data.log_id);
            } else {
                setIsSyncing(false);
                setSnackbarMsg({ open: true, message: 'Failed to start sync: ' + data.error, severity: "error" });
            }
        })
        .catch(err => {
            console.error(err);
            setIsSyncing(false);
            setSnackbarMsg({ open: true, message: 'Error starting sync', severity: "error" });
        });
    };

    if (!canRender) return null;

    return (
        <div>
            {isSyncing ? (
                <Button
                    variant="outlined"
                    color="primary"
                    disabled
                    startIcon={<CircularProgress size={20} color="inherit" />}
                    sx={{
                        "&.Mui-disabled": {
                            borderColor: "primary.main",
                            color: "primary.main",
                            opacity: 0.7,
                        }
                    }}
                >
                    {syncStatus === 'processing' ? 'Syncing...' : 'Starting...'}
                </Button>
            ) : (
                <Button
                    variant="outlined"
                    color="primary"
                    startIcon={<SyncIcon />}
                    onClick={handleClickMenu}
                >
                    Sync Data
                </Button>
            )}

            <Menu
                anchorEl={anchorEl}
                open={openMenu}
                onClose={handleCloseMenu}
            >
                {type === 'call_logs' && canSyncCalls && (
                    <MenuItem onClick={() => openSyncModal('call_logs')}>Sync Call Logs</MenuItem>
                )}
                {type === 'call_logs' && canSyncCalls && (
                    <MenuItem onClick={() => openSyncModal('recordings')}>Sync Recordings</MenuItem>
                )}
                {type === 'meetings' && canSyncMeetings && (
                    <MenuItem onClick={() => openSyncModal('meetings')}>Sync Meetings</MenuItem>
                )}
            </Menu>

            {/* Sync Date Range Modal */}
            <Dialog open={modalOpen} onClose={closeSyncModal} maxWidth="xs" fullWidth>
                <DialogTitle>Select Sync Date Range</DialogTitle>
                <DialogContent>
                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, mb: 2, mt: 1 }}>
                        <Button size="small" variant="outlined" onClick={() => setQuickDate(0)} sx={{ borderRadius: 1 }}>Today</Button>
                        <Button size="small" variant="outlined" onClick={() => setQuickDate(1)} sx={{ borderRadius: 1 }}>Yesterday</Button>
                        <Button size="small" variant="outlined" onClick={() => setQuickDate(7)} sx={{ borderRadius: 1 }}>Last 7 Days</Button>
                        <Button size="small" variant="outlined" onClick={() => setQuickDate(30)} sx={{ borderRadius: 1 }}>Last 30 Days</Button>
                    </Box>
                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 2 }}>
                        <TextField
                            label="Start Date"
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                            InputLabelProps={{ shrink: true }}
                            inputProps={{
                                min: (() => {
                                    const d = new Date();
                                    d.setMonth(d.getMonth() - 6);
                                    return d.toISOString().split('T')[0];
                                })(),
                                max: new Date().toISOString().split('T')[0]
                            }}
                            fullWidth
                        />
                        <TextField
                            label="End Date"
                            type="date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                            InputLabelProps={{ shrink: true }}
                            inputProps={{
                                min: (() => {
                                    const d = new Date();
                                    d.setMonth(d.getMonth() - 6);
                                    return d.toISOString().split('T')[0];
                                })(),
                                max: new Date().toISOString().split('T')[0]
                            }}
                            fullWidth
                        />
                    </Box>
                </DialogContent>
                <DialogActions>
                    <Button onClick={closeSyncModal}>Cancel</Button>
                    <Button onClick={triggerSync} variant="contained" color="primary">Start Sync</Button>
                </DialogActions>
            </Dialog>

            <Snackbar
                open={snackbarMsg.open}
                autoHideDuration={4000}
                onClose={handleSnackbarClose}
                anchorOrigin={{ vertical: "bottom", horizontal: "right" }}
            >
                <Alert onClose={handleSnackbarClose} severity={snackbarMsg.severity} sx={{ width: "100%" }}>
                    {snackbarMsg.message}
                </Alert>
            </Snackbar>
        </div>
    );
}
