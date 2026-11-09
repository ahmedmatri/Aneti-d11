<?php

namespace Drupal\aneti_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Provides a 'FileCopy' migrate process plugin.
 * Added the following configuration option
 * - skip_process_on_failure: (optional) Boolean, if TRUE, we throw a
 *   MigrateSkipRowException, if FALSE, throw a MigrateException exception.
 *
 * @MigrateProcessPlugin(
 *  id = "aneti_file_copy"
 * )
 */
class FileCopy extends \Drupal\migrate\Plugin\migrate\process\FileCopy {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a URI of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }
    list($source, $destination) = $value;

    // If the source path or URI represents a remote resource, delegate to the
    // download plugin.
    if (!$this->isLocalUri($source)) {
      return $this->downloadPlugin->transform($value, $migrate_executable, $row, $destination_property);
    }

    // Ensure the source file exists, if it's a local URI or path.
    if (!file_exists($source)) {
      //throw new MigrateException("File '$source' does not exist");
      throw new MigrateSkipRowException("File '$source' does not exist");
    }

    // If the start and end file is exactly the same, there is nothing to do.
    if ($this->isLocationUnchanged($source, $destination)) {
      return $destination;
    }

    // Check if a writable directory exists, and if not try to create it.
    $dir = $this->getDirectory($destination);
    // If the directory exists and is writable, avoid file_prepare_directory()
    // call and write the file to destination.
    if (!is_dir($dir) || !is_writable($dir)) {
      if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        throw new MigrateException("Could not create or write to directory '$dir'");
      }
    }

    $final_destination = $this->writeFile($source, $destination, $this->configuration['file_exists']);
    if ($final_destination) {
      return $final_destination;
    }
    //throw new MigrateException("File $source could not be copied to $destination");

    // IMPORTANT! This prevents from failing the process when file not found.
    throw new MigrateSkipRowException("File '$source' does not exist");
  }

}
