
==============================================================
GRAPHQL API
==============================================================

==============================================================
Store configuration
==============================================================

{
    storeConfig {
        orderupload_general_enabled
        orderupload_general_upload_dir
        orderupload_general_allowed_extensions
        orderupload_general_max_file_size
        orderupload_customer_customer_groups
        orderupload_customer_can_customer_orderupload
        orderupload_customer_can_delete_orderupload
        orderupload_customer_send_attachment_to_customer
        orderupload_customer_send_attachment_to_admin
        orderupload_customer_allow_checkout
        orderupload_customer_allow_comment
    }
}


==============================================================


==============================================================
Set Order Upload Comments
==============================================================

mutation {
    setOrderUploadComments(
        input: {
            cart_id: "{{cart_id}}"
            comments:"graphql comments"
        }
    ) {
            success
    }
}

==============================================================


==============================================================
Set Order Upload File in checkout
==============================================================

mutation {
    setOrderUploadFile(
        input: {
            cart_id: "{{cart_id}}"
            file:"file data"
        }
    ) {
            success
    }
}

==============================================================



==============================================================
My Order Attachments
==============================================================

{
  orderUploadCustomerCollection{
    allOrderUploadCustomer{
      id
      order_id
      customer_id
      file_name
      file_path
      comment
      visible_customer_account
      updated_at
      created_at
    }
  }
}

==============================================================


















