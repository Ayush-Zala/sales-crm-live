import { router } from "@inertiajs/react";
import { Switch } from "@mui/material";
import { confirm } from "material-ui-confirm";
import { useEffect } from "react";
import { useState } from "react";
import toast from "react-hot-toast";

const ClientBlacklistSwitch = ({ client }) => {
    const [blacklisted, setBlacklisted] = useState(client.blacklisted);

    useEffect(() => {
        setBlacklisted(client.blacklisted);
    }, [client.blacklisted]);

    const handleChange = async (event) => {
        await confirm({
            title: `${blacklisted ? "Blacklist this client" : "Whitelist"} ${
                client.name
            }!`,
            description: `Are you sure you want to ${
                blacklisted ? "blacklist this client" : "whitelist"
            } "${client.name}"?`,
            confirmationText: "Yes",
            cancellationText: "No",
            confirmationButtonProps: {
                color: blacklisted ? "error" : "success",
            },
        }).then(() => {
            // Update the client's status in the database

            router.patch(
                route("client.toggleblacklist", { id: client.id }),
                {},
                {
                    onSuccess: (response) => {
                        if (response.props.flash.error) {
                            toast.error(response.props.flash.error);
                            return;
                        }

                        setBlacklisted(!event.target.checked);
                        toast.success(response.props.flash.success);
                    },
                    onError: (error) => {
                        console.error(error);
                        toast.error("Something went wrong! Please try again.");
                    },
                }
            );

            // fetch(route("client.toggleblacklist", { id: client.id }), {
            //     method: "PATCH",
            //     headers: {
            //         "Content-Type": "application/json",
            //         "X-CSRF-TOKEN": document
            //             .querySelector('meta[name="csrf-token"]')
            //             .getAttribute("content"),
            //     },
            // })
            //     .then((response) => response.json())
            //     .then((res) => {
            //         if (res.error) {
            //             toast.error(res.error);
            //             return;
            //         }

            //         setBlacklisted(!event.target.checked);
            //         toast.success(res.message);
            //         router.get(
            //             router.page.url,
            //             {},
            //             { preserveScroll: true, preserveState: true }
            //         );
            //     })
            //     .catch((error) => {
            //         console.error(error);
            //         toast.error("Something went wrong! Please try again.");
            //     });
        });
    };

    return (
        <Switch
            checked={blacklisted}
            onChange={handleChange}
            id={`toggle-client-status-${client.id}`}
            color={blacklisted ? "error" : "success"}
        />
    );
};

export default ClientBlacklistSwitch;
