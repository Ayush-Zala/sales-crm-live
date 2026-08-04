import { yupResolver } from "@hookform/resolvers/yup";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
} from "@mui/material";
import { FormContainer, TextFieldElement, useForm } from "react-hook-form-mui";
import toast from "react-hot-toast";
import * as Yup from "yup";

const defaultValues = {
    name: "",
    description: "",
};

const schema = Yup.object().shape({
    name: Yup.string().required("Name is required"),
    description: Yup.string().required("Description is required"),
});

export default function CreateGroup({ open, handleClose }) {
    const { control, handleSubmit, reset } = useForm({
        defaultValues,
        resolver: yupResolver(schema),
    });

    const submit = (data) => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("group.store"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(data),
        })
            .then((response) => response.json())
            .then((res) => {
                toast.success(res.message);
                handleClose();
                reset();
            })
            .catch((error) => {
                console.error(error);
                toast.error("Error creating group");
            });
    };

    return (
        <Dialog
            open={open}
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
            onClose={(_, reason) => reason !== "backdropClick" && handleClose()}
        >
            <FormContainer
                defaultValues={defaultValues}
                onSuccess={handleSubmit(submit)}
            >
                <DialogTitle id="alert-dialog-title">Create Group</DialogTitle>

                <DialogContent dividers>
                    <Grid item container xs={12} spacing={2}>
                        <Grid item xs={12}>
                            <TextFieldElement
                                name="name"
                                label="Name"
                                control={control}
                            />
                        </Grid>
                        <Grid item xs={12}>
                            <TextFieldElement
                                name="description"
                                label="Desccription"
                                control={control}
                            />
                        </Grid>
                    </Grid>
                </DialogContent>
                <DialogActions>
                    <Button onClick={handleClose} color="error">
                        Disagree
                    </Button>
                    <Button type="submit" control={control}>
                        Agree
                    </Button>
                </DialogActions>
            </FormContainer>
        </Dialog>
    );
}

// <AuthenticatedLayout user={auth.user}>
//     <Head title="Create Group" />
//     <MainContentTemplate
//         title="Create Group"
//         subtitle="Create a new group here"
//         button="Go back"
//         href={route("group.index")}
//     >
//         <Grid item xs={12}>
//             <FormContainer
//                 defaultValues={defaultValues}
//                 onSuccess={handleSubmit(submit)}
//             >
//                 <Grid item container xs={12} spacing={2}>
//                     <Grid item xs={4}>
//                         <TextFieldElement
//                             name="name"
//                             label="Name"
//                             control={control}
//                         />
//                     </Grid>
//                     <Grid item xs={4}>
//                         <TextFieldElement
//                             name="description"
//                             label="Desccription"
//                             control={control}
//                         />
//                     </Grid>
//                 </Grid>
//                 <Grid item container xs={12} mt={2}>
//                     <LoadingButton type="submit" variant="contained">
//                         Submit
//                     </LoadingButton>
//                 </Grid>
//             </FormContainer>
//         </Grid>
//     </MainContentTemplate>
// </AuthenticatedLayout>
