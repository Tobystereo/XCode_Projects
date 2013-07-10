package com.example.pugpigapplication;

import android.os.Bundle;
import android.view.View;
import android.widget.FrameLayout;
import com.kaldorgroup.pugpig.app.*;
import com.kaldorgroup.pugpig.net.*;
import com.kaldorgroup.pugpig.ui.*;

public class DocumentPickerViewController extends StandardViewController implements DocumentPickerDelegate {
  private DocumentPicker documentPicker;
  private DocumentManager documentManager;

  public DocumentPickerViewController() {
    super(R.layout.documentpickerview);
  }

  @Override
  public void init() {
    super.init();
    documentManager = DocumentManager.sharedManager();
  }

  @Override
  protected void onCreate(Bundle savedInstanceState) {
    super.onCreate(savedInstanceState);

    // We need to set the size here rather that in viewDidLoad otherwise it won't have taken effect by the
    // time the size is queried in various places called from viewDidLoad.
    DocumentPicker documentPicker = (DocumentPicker)findViewById(R.id.documentPicker);
    FrameLayout.LayoutParams documentPickerLp = (FrameLayout.LayoutParams)documentPicker.getLayoutParams();
    documentPickerLp.height = Math.round(Application.deviceWidth() * 0.80f);
    documentPicker.setLayoutParams(documentPickerLp);
    documentPicker.requestLayout();
  }

  @Override
  public void viewDidLoad() {
    super.viewDidLoad();

    documentPicker = (DocumentPicker)findViewById(R.id.documentPicker);
    documentPicker.setDelegate(this);
    documentPicker.setShouldShowDocumentName(true);
    documentPicker.setShouldShowDownloadButton(true);
    documentPicker.setShouldSnap(true);
    documentPicker.addActionForControlEvents(this, "pickDocument", ControlEvents.ValueChanged);
    documentPicker.setBlankErrorMessage("No documents available");

    // TODO: Once we make sure viewDidLoad is always called before viewWillAppear we can get rid of this line.
    refreshDocumentPicker();
  }

  @Override
  public void viewDidUnload() {
    super.viewDidUnload();
  }

  @Override
  public void viewWillAppear() {
    super.viewWillAppear();
    refreshDocumentPicker();
  }

  @Override
  public void viewDidAppear() {
    super.viewDidAppear();
  }

  @Override
  public void viewWillDisappear() {
    super.viewWillDisappear();
  }

  @Override
  public void viewDidDisappear() {
    super.viewDidDisappear();
    documentPicker.setDocuments(null);
  }

  @Override
  public void didReceiveMemoryWarning() {
    super.didReceiveMemoryWarning();
  }

  public void pickDocument() {
    Document newDocument = documentPicker.selectedDocument();
    AppDelegate delegate = (AppDelegate)Application.delegate();
    delegate.openDocument(newDocument);
  }

  private void setAlpha(float alpha, View view) {
    View cover = view.findViewWithTag(DocumentPickerTags.Cover);
    if (cover != null) ViewUtils.setAlpha(cover, alpha);
    View label = view.findViewWithTag(DocumentPickerTags.Label);
    if (label != null) ViewUtils.setAlpha(label, alpha);
    View button = view.findViewWithTag(DocumentPickerTags.DownloadButton);
    if (button != null) ViewUtils.setAlpha(button, alpha);
    View progress = view.findViewWithTag(DocumentPickerTags.ProgressBar);
    if (progress != null) ViewUtils.setAlpha(progress, alpha);
  }

  public void documentPickerDidAddControlsForDocument(DocumentPicker picker, Document document, View view) {
    setAlpha(0.25f, view);
  }

  public void documentPickerWillRenderDocument(DocumentPicker picker, Document document, View view, float offset) {
    offset = Math.abs(offset);
    if (offset > 1.0) offset = 1.0f;
    setAlpha(1.0f - 0.75f*offset, view);
  }

  public void refreshDocumentPicker() {
    // On Android it's possible for this method to be called before the viewDidLoad,
    // so the documentPicker may not have been initialised yet.
    if (documentPicker == null) return;

    AppDelegate delegate = (AppDelegate)Application.delegate();
    if (delegate.isLoading())
      documentPicker.setDocuments(null);
    else
      documentPicker.setDocuments(documentManager.documents());

    documentPicker.requestLayout();
    documentPicker.invalidate();
  }
}
