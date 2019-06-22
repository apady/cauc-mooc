<?php
/**
 * @author lishen chen frankchenls@outlook.com
 */
namespace App;

final class FileUploadEvents{
    /**
     * The CHUNK_ARRIVED event occurs once a file chunk arrives at the server.
     */
    const CHUNK_ARRIVED='file.chunk_arrived';

    /**
     * The MERGE event occurs when a file chunk can be merged into the file .
     */
    const MERGE='file.merge';

    /**
     * The MERGE_COMPLETED event occurs when all of the  chunks are successfully merge into a file.
     */
    const MERGE_COMPLETED='file.merge_completed';

}