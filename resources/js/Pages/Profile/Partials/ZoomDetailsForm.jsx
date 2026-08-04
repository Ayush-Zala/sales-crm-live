import TextInput from "@/Components/TextInput";
import SnackBar from "@/Layouts/components/snack-bar";
import { useForm } from "@inertiajs/react";
import { LoadingButton } from "@mui/lab";
import { Grid } from "@mui/material";
import toast from "react-hot-toast";

const ZoomDetailsForm = ({ zoomApiDetails }) => {
    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            accountId: zoomApiDetails?.account_id || "",
            clientKey: zoomApiDetails?.client_key || "",
            clientSecret: zoomApiDetails?.client_secret || "",
        });

    const submit = (e) => {
        e.preventDefault();

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("zoomdetails.update"), {
            method: "PATCH",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(data),
        })
            .then((response) => response.json())
            .then((data) => {
                toast.success(data.message);
            })
            .catch((error) => {
                console.error("Error:", error);
                toast.error("Error saving zoom credentials !!");
            });
    };

    return (
        <Grid container spacing={2} mt={2} alignItems="center">
            <Grid item xs={3}>
                <TextInput
                    id="accountId"
                    label="Account ID"
                    value={data.accountId}
                    onChange={(e) => setData("accountId", e.target.value)}
                    required
                    isFocused
                    autoComplete="accountId"
                    error={errors.accountId}
                    helperText={errors.accountId}
                />
            </Grid>

            <Grid item xs={3}>
                <TextInput
                    id="clientKey"
                    type="clientKey"
                    value={data.clientKey}
                    onChange={(e) => setData("clientKey", e.target.value)}
                    required
                    autoComplete="clientKey"
                    label="Client Key"
                    error={errors.clientKey}
                    helperText={errors.clientKey}
                />
            </Grid>

            <Grid item xs={3}>
                <TextInput
                    id="clientSecret"
                    type="clientSecret"
                    value={data.clientSecret}
                    onChange={(e) => setData("clientSecret", e.target.value)}
                    required
                    autoComplete="clientSecret"
                    label="Client Secret"
                    error={errors.clientSecret}
                    helperText={errors.clientSecret}
                />
            </Grid>

            <Grid item xs={3}>
                <LoadingButton
                    variant="contained"
                    loading={processing}
                    onClick={submit}
                >
                    Save
                </LoadingButton>
            </Grid>
            <SnackBar open={recentlySuccessful} message="Profile saved !!" />
        </Grid>
    );
};

export default ZoomDetailsForm;
