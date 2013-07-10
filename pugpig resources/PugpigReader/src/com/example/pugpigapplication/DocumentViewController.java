package com.example.pugpigapplication;

import android.content.res.Configuration;
import android.view.*;
import android.widget.Button;
import android.widget.TextView;
import com.kaldorgroup.pugpig.app.*;
import com.kaldorgroup.pugpig.datasource.*;
import com.kaldorgroup.pugpig.net.*;
import com.kaldorgroup.pugpig.ui.*;

import java.net.URL;
import java.util.Arrays;

public class DocumentViewController extends StandardViewController implements View.OnClickListener, PagedDocControlDelegate {
  private View toolbar;
  private PagedDocControlEx pageControl;
  private PagedDocThumbnailControl thumbnailControl;
  private TableOfContentsControl tableOfContents;
  private DocumentManager documentManager;
  private Button tocButton;

  public DocumentViewController() {
    super(R.layout.documentview);
  }

  @Override
  public void init() {
    super.init();
    documentManager = DocumentManager.sharedManager();
  }

  @Override
  public void viewDidLoad() {
    super.viewDidLoad();

    toolbar = (View)findViewById(R.id.toolbar);
    toolbar.bringToFront();
    toolbar.setVisibility(View.GONE);
    findViewById(R.id.tocButton).setOnClickListener(this);
    findViewById(R.id.documentPickerButton).setOnClickListener(this);
    findViewById(R.id.fontSizeButton).setOnClickListener(this);

    pageControl = (PagedDocControlEx)findViewById(R.id.pagedDocControl);

    thumbnailControl = (PagedDocThumbnailControl)findViewById(R.id.thumbnailControl);
    thumbnailControl.setVisibility(View.GONE);

    thumbnailControl.setPageSeparation(10);
    Size thumbSize = computeThumbSize();
    thumbnailControl.setPortraitSize(new Size(thumbSize.height, thumbSize.width));
    thumbnailControl.setLandscapeSize(new Size(thumbSize.width, thumbSize.height));

    tocButton = (Button)findViewById(R.id.tocButton);
    tableOfContents = (TableOfContentsControl)findViewById(R.id.tableOfContentsControl);
    tableOfContents.addActionForControlEvents(this, "contentsSelectionChanged", ControlEvents.ValueChanged);
    tableOfContents.setVisibility(View.GONE);

    if (Application.screenSize() <= Configuration.SCREENLAYOUT_SIZE_NORMAL) {
      TextView title = (TextView)findViewById(R.id.toolbarTitle);
      title.setVisibility(View.GONE);
    }

    pageControl.setDelegate(this);
    pageControl.setImageViewingEnabled(true);
    pageControl.setMonitorPageUpdates(true);
    pageControl.setScrollEnabled(true);
    pageControl.initFontSizeFromUserDefaults();
    pageControl.setNavigator(thumbnailControl);

    pageControl.addGestureListener(new GestureDetector.SimpleOnGestureListener() {
      public boolean onDoubleTap(MotionEvent e) {
        onDoubleClick();
        return false;
      }
    });
  }

  @Override
  public void viewDidUnload() {
    tableOfContents.removeActionForControlEvents(this, "contentsSelectionChanged", ControlEvents.ValueChanged);
    pageControl.setMonitorPageUpdates(false);
    pageControl.destroy();
    toolbar = null;
    pageControl = null;
    thumbnailControl = null;
    tableOfContents = null;
    tocButton = null;
    super.viewDidUnload();
  }

  @Override
  public void viewWillAppear() {
    super.viewWillAppear();
  }

  @Override
  public void viewDidAppear() {
    super.viewDidAppear();
    pageControl.startSnapshotting();
  }

  @Override
  public void viewWillDisappear() {
    super.viewWillDisappear();
    pageControl.stopSnapshotting();
  }

  @Override
  public void viewDidDisappear() {
    super.viewDidDisappear();
  }

  @Override
  public void didReceiveMemoryWarning() {
    super.didReceiveMemoryWarning();
    pageControl.imageStore().releaseMemory();
    thumbnailControl.releaseMemory();
  }

  public void openDocument(Document document) {
    openDocument(document, null);
  }

  public void openDocument(Document document, Object position) {
    // We set the currentlyReadingDocument to null before setting it for real
    // to force a reset in case we're reopening the same document.
    documentManager.setCurrentlyReadingDocument(null);
    documentManager.setCurrentlyReadingDocument(document);

    pageControl.setImageStore(document.imageStore());
    pageControl.setDataSource(document.dataSource());

    if (position != null)
      pageControl.restorePosition(position);
    else
      pageControl.setPageNumber(0);

    DocumentDataSource dataSource = document.dataSource();
    if (!Arrays.asList(dataSource.getClass().getInterfaces()).contains(DocumentExtendedDataSource.class)) {
      tocButton.setEnabled(false);
    }
    else {
      tocButton.setEnabled(true);
      tableOfContents.setDataSource((DocumentExtendedDataSource)dataSource);
    }
  }

  public boolean documentDidClickLink(PagedDocControl doc, URL url) {
    return false;
  }

  public void documentDidExecuteCommand(PagedDocControl doc, URL url) {
    String command = url.getHost();
    if (command != null && command.equals("onImageClick")) {
      String imageID = url.getPath();
      if (imageID != null && imageID.length() > 1) imageID = imageID.substring(1);
      ViewLauncher launcher = ViewLauncher.launcherForClass(ImageViewController.class, doc, imageID);
      presentViewController(launcher);
    }
  }

  private Size computeThumbSize() {
    float d = getResources().getDisplayMetrics().density;
    float dw = getResources().getDisplayMetrics().widthPixels;
    float dh = getResources().getDisplayMetrics().heightPixels;
    if (dh > dw) {
      float temp = dh;
      dh = dw;
      dw = temp;
    }
    float scale = Math.min(1f/3f, d/5f);
    int thumbWidth = (int)(scale * dw);
    int thumbHeight = (int)(scale * dh);
    return new Size(thumbWidth, thumbHeight);
  }

  private void hideToolbar(boolean hidden) {
    Animation.setHidden(toolbar, hidden, Animation.Style.SlideUp | Animation.Style.Fade, 0.5f);
  }

  private void hideToolbarControls() {
//  [searchControl setHidden:YES animationStyle:KGAnimationSlideRight duration:0.5];
    Animation.setHidden(tableOfContents, true, Animation.Style.SlideLeft, 0.5f);
  }

  private void hideAllControls() {
    hideToolbarControls();
    Animation.setHidden(thumbnailControl, true, Animation.Style.SlideDown | Animation.Style.Fade, 0.5f);
    thumbnailControl.setHidden(true);
  }

  private void toggleNavigator() {
    boolean hide = toolbar.getVisibility() == View.VISIBLE;
    hideToolbar(hide);
    if (hide)
      hideAllControls();
    else {
      Animation.setHidden(thumbnailControl, hide, Animation.Style.SlideDown | Animation.Style.Fade, 0.5f);
      thumbnailControl.setHidden(hide);
    }
  }

  private void toggleTableOfContents() {
    boolean hide = tableOfContents.getVisibility() == View.VISIBLE;
    if (!hide) hideAllControls();
    Animation.setHidden(tableOfContents, hide, Animation.Style.SlideLeft | Animation.Style.Fade, 0.5f);
  }

  public void contentsSelectionChanged() {
    int page = tableOfContents.selectedPageNumber();
    pageControl.setPageNumber(page, true);
    if (tableOfContents.getVisibility() == View.VISIBLE) toggleTableOfContents();
  }

  public void onClick(View v) {
    switch (v.getId()) {
      case R.id.documentPickerButton:
        dismissViewController();
        break;
      case R.id.fontSizeButton:
        documentManager.clearRenderState();
        pageControl.cycleFontSizeFrom(1, 3, 1, false);
        pageControl.saveFontSizeToUserDefaults();
        break;
      case R.id.tocButton:
        tableOfContents.bringToFront();
        toggleTableOfContents();
        break;
    }
  }

  public void onDoubleClick() {
    toggleNavigator();
  }

  public boolean onKeyUp(int keyCode, KeyEvent event) {
    if (keyCode == KeyEvent.KEYCODE_MENU) {
      toggleNavigator();
      return true;
    }
    return super.onKeyUp(keyCode, event);
  }
}
