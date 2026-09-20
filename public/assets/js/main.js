function confirmDeleteBook(bookId, bookName) {
    Swal.fire({
        title: "Are you sure you want to delete " + bookName + " ?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: "Deleted!",
                text: "Your book has been deleted.",
                icon: "success",
            });
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "/books-app/admin/books/" + bookId + "/delete_book";

            document.body.appendChild(form);
            form.submit();
        }
    });
}

function confirmDeleteAuthor(authorId, authorName) {
    Swal.fire({
        title: "Are you sure you want to delete " + authorName + " ?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: "Deleted!",
                text: "The author has been deleted.",
                icon: "success",
            });
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "/books-app/admin/authors/" + authorId + "/delete_author";

            document.body.appendChild(form);
            form.submit();
        }
    });
}
