import useUpdateSearchParam from "@/hooks/use-update-search-params";
import { useSelectionStore } from "@/store/selection-store";
import Person4Rounded from "@mui/icons-material/Person4Rounded";

import LoadingButton from "@mui/lab/LoadingButton";

import Avatar from "@mui/material/Avatar";
import Badge from "@mui/material/Badge";
import Button from "@mui/material/Button";
import Dialog from "@mui/material/Dialog";
import DialogActions from "@mui/material/DialogActions";
import DialogContent from "@mui/material/DialogContent";
import DialogTitle from "@mui/material/DialogTitle";
import List from "@mui/material/List";
import ListItem from "@mui/material/ListItem";
import ListItemAvatar from "@mui/material/ListItemAvatar";
import ListItemButton from "@mui/material/ListItemButton";
import ListItemText from "@mui/material/ListItemText";
import Stack from "@mui/material/Stack";
import Typography from "@mui/material/Typography";

import { map } from "lodash";
import { confirm } from "material-ui-confirm";
import toast from "react-hot-toast";

const AssignUserDialog = ({ open, handleClose, users, data }) => {
    const { setSelection } = useSelectionStore();

    const handleClick = (user_id) => {
        confirm({
            title: "Assign Account",
            description: "Are you sure you want to assign this account?",
            confirmationText: "Yes",
            cancellationText: "No",
        }).then(() => {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");

            fetch(route("account.checkcompanyassigned"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    companyIds: data.map((company) => company.id),
                    userId: user_id,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.error) {
                        toast.error(data.error);
                        return;
                    }
                    toast.success(data.message);
                    handleClose();
                    setSelection([]);
                    useUpdateSearchParam({ page: 1, per_page: 50 });
                })
                .catch((error) => {
                    console.error(error);
                });
        });
    };

    const handleUnassignClick = () => {
        confirm({
            title: "Unassign Account",
            description: "Are you sure you want to Unassign this account?",
            confirmationText: "Yes",
            cancellationText: "No",
        }).then(() => {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");

            fetch(route("account.unassigncompany"), {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    companyIds: data.map((company) => company.id),
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    toast.success(data.message);
                    handleClose();
                    setSelection([]);
                    useUpdateSearchParam({ page: 1, per_page: 50 });
                })
                .catch((error) => {
                    console.error(error);
                });
        });
    };

    return (
        <Dialog
            fullWidth
            open={open}
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
        >
            <DialogTitle
                component={Stack}
                spacing={0.5}
                direction={{ lg: "row", xs: "column" }}
                alignItems={{ lg: "center", xs: "flex-start" }}
                justifyContent={{ lg: "space-between", xs: "flex-start" }}
            >
                <Typography fontSize="inherit" fontWeight="inherit">
                    Assign Account to User
                </Typography>
                <LoadingButton
                    variant="contained"
                    color="error"
                    onClick={handleUnassignClick}
                >
                    Unassign Account
                </LoadingButton>
            </DialogTitle>

            <DialogContent dividers sx={{ p: 0 }}>
                <List disablePadding dense>
                    {map(users, (user) => (
                        <ListItem
                            disablePadding
                            divider={users[users.length - 1].id !== user.id}
                            key={user.id}
                        >
                            <ListItemButton
                                onClick={() => handleClick(user.id)}
                            >
                                <ListItemAvatar>
                                    <Badge
                                        color={
                                            user.isOnline ? "success" : "error"
                                        }
                                        overlap="circular"
                                        badgeContent=" "
                                        variant="dot"
                                        anchorOrigin={{
                                            vertical: "bottom",
                                            horizontal: "right",
                                        }}
                                    >
                                        <Avatar>
                                            <Person4Rounded />
                                        </Avatar>
                                    </Badge>
                                </ListItemAvatar>
                                <ListItemText
                                    primary={user.name}
                                    secondary={user.email}
                                />
                                <ListItemText
                                    primary={user.reporting_authority.name}
                                    sx={{
                                        textAlign: "right",
                                        color: "warning.main",
                                    }}
                                />
                            </ListItemButton>
                        </ListItem>
                    ))}
                </List>
            </DialogContent>
            <DialogActions>
                <Button onClick={handleClose}>Close</Button>
            </DialogActions>
        </Dialog>
    );
};

export default AssignUserDialog;
