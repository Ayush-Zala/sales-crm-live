import { router } from "@inertiajs/react";
import { Chip, FormControlLabel, Switch } from "@mui/material";
import { confirm } from "material-ui-confirm";
import { useState } from "react";
import toast from "react-hot-toast";

const IsActiveSwitch = ({ user }) => {
    const [isActive, setIsActive] = useState(user.is_active);

    const handleChange = async (event) => {
        await confirm({
            title: `${
                isActive
                    ? "Deactivate and Unassign all the accounts from"
                    : "Activate"
            } ${user.name}!`,
            description: `Are you sure you want to ${
                isActive
                    ? "deactivate and Unassign all the accounts from"
                    : "activate"
            } "${user.name}"?`,
            confirmationText: "Yes",
            cancellationText: "No",
            confirmationButtonProps: { color: isActive ? "error" : "success" },
        }).then(() => {
            const url = router.page.url;

            // Update the user's status in the database
            fetch(route("user.toggleActiveStatus"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    id: user.id,
                    is_active: !event.target.checked,
                }),
            })
                .then((response) => response.json())
                .then((res) => {
                    if (res.error) {
                        toast.error(res.error);
                        return;
                    }

                    setIsActive(!event.target.checked);
                    toast.success(res.message);
                    router.get(
                        url,
                        {},
                        { preserveScroll: true, preserveState: true }
                    );
                })
                .catch((error) => {
                    console.error(error);
                    toast.error("Something went wrong! Please try again.");
                });
        });
    };

    return (
        <FormControlLabel
            control={
                <Switch
                    size="small"
                    checked={isActive}
                    onChange={handleChange}
                    id={`toggle-user-status-${user.id}`}
                    color={isActive ? "success" : "error"}
                />
            }
            label={
                <Chip
                    size="small"
                    color={isActive ? "success" : "error"}
                    label={isActive ? "Active" : "Inactive"}
                />
            }
            disabled={user.name === "admin" || user.name === "Admin"}
        />
    );
};

export default IsActiveSwitch;
