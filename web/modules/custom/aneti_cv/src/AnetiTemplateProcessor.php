<?php

namespace Drupal\aneti_cv;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Shared\ZipArchive;

/**
 * Class PhpWord.
 */
class AnetiTemplateProcessor extends TemplateProcessor {

  public function __construct() {
  }

  public function init($documentTemplate) {
    // Temporary document filename initialization
    $this->tempDocumentFilename = tempnam(Settings::getTempDir(), 'PhpWord');
    if (false === $this->tempDocumentFilename) {
      throw new CreateTemporaryFileException(); // @codeCoverageIgnore
    }

    // Template file cloning
    if (false === copy($documentTemplate, $this->tempDocumentFilename)) {
      throw new CopyFileException($documentTemplate, $this->tempDocumentFilename); // @codeCoverageIgnore
    }

    // Temporary document content extraction
    $this->zipClass = new ZipArchive();
    $this->zipClass->open($this->tempDocumentFilename);
    $index = 1;
    while (false !== $this->zipClass->locateName($this->getHeaderName($index))) {
      $this->tempDocumentHeaders[$index] = $this->readPartWithRels($this->getHeaderName($index));
      $index++;
    }
    $index = 1;
    while (false !== $this->zipClass->locateName($this->getFooterName($index))) {
      $this->tempDocumentFooters[$index] = $this->readPartWithRels($this->getFooterName($index));
      $index++;
    }

    $this->tempDocumentMainPart = $this->readPartWithRels($this->getMainPartName());
    $this->tempDocumentContentTypes = $this->zipClass->getFromName($this->getDocumentContentTypesName());
  }
}
